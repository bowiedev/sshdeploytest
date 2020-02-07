<?php
function_exists('add_action') or die;

include_once AUTOUPDATER_WP_PLUGIN_PATH . 'app/Admin.php';
include_once AUTOUPDATER_WP_PLUGIN_PATH . 'app/Whitelabelling.php';

class AutoUpdater_WP_Application
{
    protected static $instance = null;
    protected $slug = '';
    protected $plugin_filename = '';

    /**
     * @return static
     */
    public static function getInstance()
    {
        if (!is_null(static::$instance)) {
            return static::$instance;
        }

        static::$instance = new AutoUpdater_WP_Application();

        return static::$instance;
    }

    public function __construct()
    {
        add_action('init', array($this, 'siteOffline'));
        add_action('plugins_loaded', array($this, 'loadLanguages'));
        add_filter('mod_rewrite_rules', array($this, 'setHtaccessRules'));

        AutoUpdater_WP_Whitelabelling::getInstance();
        AutoUpdater_WP_Admin::getInstance();
    }

    public function loadLanguages()
    {
        load_plugin_textdomain('autoupdater', false, 'autoupdater/lang');
    }

    /**
     * @param string $rules Rewrite rules formatted for .htaccess.
     *
     * @return string
     */
    public function setHtaccessRules($rules)
    {
        $backuptool_dir = AutoUpdater_Backuptool::getInstance()->getDir();
        if (empty($backuptool_dir)) {
            return $rules;
        }
        $backuptool_rule = 'RewriteRule ^autoupdater_(backup_[a-zA-Z0-9]+|restore)/ - [L]';
        $lines = explode("\n", $rules);

        // The Backup Tool rule already exists
        if (array_search($backuptool_rule, $lines) !== false) {
            return $rules;
        }

        // Add the Backup Tool rule before a rule we are searching for
        if (($index = array_search('RewriteRule ^index\.php$ - [L]', $lines)) !== false) {
            array_splice($lines, $index, 0, $backuptool_rule);
        } elseif (($index = array_search('</IfModule>', $lines)) !== false) {
            array_splice($lines, $index, 0, $backuptool_rule);
        }

        return implode("\n", $lines);
    }

    public function siteOffline()
    {
        global $pagenow;

        // Allow to log in to the back-end and white list the AutoUpdater service
        if (is_admin() || $pagenow == 'wp-login.php' || isset($_GET['autoupdater_nonce']) || php_sapi_name() == 'cli') {
            return;
        }

        if (function_exists('getallheaders')) {
            foreach (getallheaders() as $name => $value) {
                if (stristr($name, 'autoupdater-')) {
                    return;
                }
            }
        }

        if (!AutoUpdater_Maintenance::getInstance()->isEnabled()) {
            return;
        }

        $path = WP_CONTENT_DIR . '/autoupdater/tmpl/offline.tmpl.php';
        if (!AutoUpdater_Filemanager::getInstance()->exists($path)) {
            $path = AUTOUPDATER_WP_PLUGIN_PATH . 'tmpl/offline.tmpl.php';
        }

        ob_start();
        include $path;
        $body = ob_get_clean();

        AutoUpdater_Response::getInstance()
            ->setCode(503)
            ->setMessage('Service Unavailable')
            ->setHeader('Retry-After', '3600')
            ->setAutoupdaterHeader()
            ->setBody($body)
            ->send();
    }
}
