<?php
defined('AUTOUPDATER_LIB') or die;

class AutoUpdater_Api
{
    /**
     * @var static|null
     */
    protected static $instance = null;

    /**
     * @var bool
     */
    protected $initialized = false;

    /**
     * @var AutoUpdater_Task|null
     */
    protected $task = null;

    /**
     * @return static
     */
    public static function getInstance()
    {
        if (!is_null(static::$instance)) {
            return static::$instance;
        }

        $class_name = AutoUpdater_Loader::loadClass('Api');

        static::$instance = new $class_name();

        return static::$instance;
    }

    public function __construct()
    {
        // Initialize AutoUpdater API if met the minimal requirements
        $method = isset($_SERVER['REQUEST_METHOD']) ? strtolower($_SERVER['REQUEST_METHOD']) : null;
        if (isset($_REQUEST['autoupdater']) && $_REQUEST['autoupdater'] == 'api' &&
            isset($_REQUEST['wpe_endpoint']) &&
            isset($_REQUEST['wpe_signature']) &&
            in_array($method, array('get', 'post'))
        ) {
            $this->init();
        }
    }

    /**
     * @return bool
     */
    public function isInitialized()
    {
        return $this->initialized;
    }

    protected function init()
    {
        $method = isset($_SERVER['REQUEST_METHOD']) ? strtolower($_SERVER['REQUEST_METHOD']) : null;

        // Get all data from the request
        $payload = array();
        foreach ($_REQUEST as $key => $value) {
            if (substr($key, 0, 4) == 'wpe_' && $key != 'wpe_signature') {
                $payload[$key] = urldecode($value);
            }
        }
        // Sort the request data by keys, to generate a correct signature from the payload
        ksort($payload);

        if ($method == 'post') {
            // Get the request JSON body
            $body = file_get_contents('php://input');
            // Do not decode JSON before validation
            $payload['json'] = is_string($body) ? $body : '';
        }

        try
        {
            // Validate the request payload
            $auth = AutoUpdater_Authentication::getInstance();
            $auth->validate($payload);
        } catch (Exception $e) {
            AutoUpdater_Response::getInstance()
                ->setCode(403)
                ->setAutoupdaterHeader()
                ->setBody(array(
                    'error' => array(
                        'code' => $e->getCode(),
                        'message' => $e->getMessage(),
                    ),
                ))
                ->sendJSON();
        }

        // Decode JSON
        if (isset($payload['json'])) {
            try
            {
                $json = json_decode($payload['json'], true);
                unset($payload['json']);
                $payload = array_merge($payload, $json);
            } catch (Exception $e) {
                AutoUpdater_Response::getInstance()
                    ->setCode(400)
                    ->setAutoupdaterHeader()
                    ->setData(array(
                        'success' => false,
                        'message' => 'Failed to decode JSON',
                        'error' => array(
                            'code' => $e->getCode(),
                            'message' => $e->getMessage(),
                        ),
                    ))
                    ->sendJSON();
            }
        }

        // Parse the endpoint and create the task name
        $endpoint = strtolower($payload['wpe_endpoint']);
        $task = str_replace(' ', '', ucwords($method . ' ' . str_replace('/', ' ', $endpoint)));

        register_shutdown_function(array($this, 'catchError'));

        // Get the task
        try
        {
            $this->task = AutoUpdater_Task::getInstance($task, $payload);
        } catch (Exception $e) {
            AutoUpdater_Response::getInstance()
                ->setCode(400)
                ->setAutoupdaterHeader()
                ->setData(array(
                    'success' => false,
                    'message' => 'Failed to initialize task ' . $task,
                    'error' => array(
                        'code' => $e->getCode(),
                        'message' => $e->getMessage(),
                    ),
                ))
                ->sendJSON();
        }

        $this->initialized = true;

        if (is_admin()) {
            if (defined('DOING_AJAX')) {
                add_filter('github_updater_add_admin_pages', array($this, 'addAdminPagesToGitHubUpdater'));
                add_action('wp_ajax_autoupdater_api', array($this, 'handle'), 10);
                add_action('wp_ajax_nopriv_autoupdater_api', array($this, 'handle'), 10);
            }
        } else {
            $priority = (isset($_REQUEST['wpe_endpoint']) && $_REQUEST['wpe_endpoint'] === 'extension/update') ? 10 : 1;
            add_action('init', array($this, 'handle'), $priority);
        }
    }

    public function handle()
    {
        if (!$this->initialized) {
            return;
        }

        // set the en_US as a default
        load_default_textdomain('en_US');

        if (!AutoUpdater_Config::get('ssl_verify', 0)) {
            add_filter('http_request_args', array($this, 'hookDisableSslVerification'), 10, 2);
        }

        AutoUpdater_defineErrorClass();
        AutoUpdater_Config::set('ping', time());

        if (($site_id = (int)$this->task->input('site_id'))) {
            AutoUpdater_Config::set('site_id', $site_id);
        }

        $response = AutoUpdater_Response::getInstance();
        AutoUpdater_Log::debug('Doing task ' . $this->task->getName());
        try
        {
            $data = $this->task->doTask();
            $response->setData($data);

            AutoUpdater_Log::debug('Task ' . $this->task->getName()
                . ' ended with result: ' . print_r($response->data, true)
            );
        } catch (AutoUpdater_Exception_Response $e) {
            $data = array(
                'success' => false,
                'message' => $e->getMessage(),
            );

            if ($e->getErrorCode()) {
                $data['error'] = array(
                    'code' => $e->getErrorCode(),
                    'message' => $e->getErrorMessage(),
                );
            }

            $response->setData($data)
                ->setCode($e->getCode());

            AutoUpdater_Log::error('Task ' . $this->task->getName()
                . ' ended with response exception: ' . print_r($response->data, true)
            );
        } catch (Error $e) {
            // Catch a fatal error thrown by PHP 7
            $filemanager = AutoUpdater_Filemanager::getInstance();
            $message = sprintf('%s on line %d in file %s',
                $e->getMessage(),
                $e->getLine(),
                $filemanager->trimPath($e->getFile())
            );

            $response->setCode(500)
                ->setData(array(
                    'success' => false,
                    'message' => 'PHP fatal error',
                    'error' => array(
                        'code' => 0,
                        'message' => $message,
                    ),
                ));

            AutoUpdater_Log::error('Task ' . $this->task->getName()
                . ' ended with PHP fatal error: ' . $message . " \n"
                . 'Error trace: ' . $e->getTraceAsString()
            );
        } catch (Exception $e) {
            $response->setData(array(
                'success' => false,
                'message' => $e->getCode() . ' ' . $e->getMessage(),
            ));

            AutoUpdater_Log::error('Task ' . $this->task->getName()
                . ' ended with exception: ' . print_r($response->data, true) . " \n"
                . 'Exception trace: ' . $e->getTraceAsString()
            );
        }

        $response->setAutoupdaterHeader()
            ->setEncryption($this->task->isEncryptionRequired())
            ->sendJSON();
    }

    /**
     * Catch a fatal error thrown by PHP 5
     */
    public function catchError()
    {
        $error = error_get_last();
        // fatal error, E_ERROR === 1
        if ($error['type'] === E_ERROR) {
            $filemanager = AutoUpdater_Filemanager::getInstance();
            $message = sprintf('%s on line %d in file %s',
                $error['message'],
                $error['line'],
                $filemanager->trimPath($error['file'])
            );

            $response = AutoUpdater_Response::getInstance()
                ->setCode(500)
                ->setAutoupdaterHeader()
                ->setData(array(
                    'success' => false,
                    'message' => 'PHP fatal error',
                    'error' => array(
                        'code' => 0,
                        'message' => $message,
                    ),
                ));

            if (!is_null($this->task)) {
                $message = 'Task ' . $this->task->getName()
                    . ' ended with PHP fatal error: ' . $message;
            }
            AutoUpdater_Log::error($message);

            $response->sendJSON();
        }
    }

    /**
     * @param array  $r
     * @param string $url
     *
     * @return array
     */
    public function hookDisableSslVerification($r, $url)
    {
        $r['sslverify'] = false;

        return $r;
    }

    /**
     * Filter $admin_pages to be able to adjust the pages where GitHub Updater runs.
     *
     * @since GitHub Updater 8.0.0
     *
     * @param array $admin_pages Default array of admin pages where GitHub Updater runs.
     * 
     * @return array
     */
    public function addAdminPagesToGitHubUpdater($admin_pages = array())
    {
        if (is_array($admin_pages)) {
            $admin_pages[] = 'admin-ajax.php';
        }
        return $admin_pages;
    }
}

/**
 * Define Error class thrown by PHP 7 when a fatal error occurs for a backward compatibility with PHP 5
 */
function AutoUpdater_defineErrorClass()
{
    if (version_compare(PHP_VERSION, '7.0', '<') && !class_exists('Error')) {
        class Error extends Exception
        {
        }
    }
}
