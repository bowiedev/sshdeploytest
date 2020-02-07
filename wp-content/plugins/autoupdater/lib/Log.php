<?php
defined('AUTOUPDATER_LIB') or die;

class AutoUpdater_Log
{
    protected static $instance = null;

    /**
     * @return static
     */
    public static function getInstance()
    {
        if (!is_null(static::$instance)) {
            return static::$instance;
        }

        $class_name = AutoUpdater_Loader::loadClass('Log');

        static::$instance = new $class_name();

        return static::$instance;
    }

    /**
     * @param string $message
     */
    public static function info($message)
    {
        static::getInstance()->log('info', $message);
    }

    /**
     * @param string $message
     */
    public static function debug($message)
    {
        static::getInstance()->log('debug', $message);
    }

    /**
     * @param string $message
     */
    public static function error($message)
    {
        static::getInstance()->log('error', $message);
    }

    /**
     * @return string
     */
    public function getLogsPath()
    {
        return rtrim(WP_CONTENT_DIR, '/\\') . '/.logs/';
    }

    /**
     * @param string $level
     * @param string $message
     */
    public function log($level = 'debug', $message)
    {
        if (!AutoUpdater_Config::get('debug') && $level != 'error') {
            return;
        }

        $path = $this->getLogsPath();
        $file = 'autoupdater_' . date('Y-m-d') . '.logs.php';
        $filemanager = AutoUpdater_Filemanager::getInstance();

        if (!$filemanager->is_dir($path)) {
            $filemanager->mkdir($path);
        }

        if (!$filemanager->exists($path . $file)) {
            file_put_contents($path . $file, '<?php die(); ?>');
        }

        $level = strtoupper($level);
        $date = date('Y-m-d H:i:s');

        file_put_contents($path . $file,
            "\n[$date] $level $message", FILE_APPEND);
    }
}
