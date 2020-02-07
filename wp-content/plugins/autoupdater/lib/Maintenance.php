<?php
defined('AUTOUPDATER_LIB') or die;

class AutoUpdater_Maintenance
{
    /**
     * @var string
     */
    protected $path = '';
    protected static $instance = null;

    /**
     * @return static
     */
    public static function getInstance()
    {
        if (!is_null(static::$instance)) {
            return static::$instance;
        }

        $class_name = AutoUpdater_Loader::loadClass('Maintenance');

        static::$instance = new $class_name();

        return static::$instance;
    }

    public function __construct()
    {
        $this->path = AUTOUPDATER_SITE_PATH . 'autoupdater_maintenance_mode_enabled.tmp';
    }

    public function isEnabled()
    {
        return AutoUpdater_Filemanager::getInstance()->exists($this->path);
    }

    public function enable()
    {
        $date = current_time(DATE_ATOM, true);
        $maintenance_enabled = AutoUpdater_Filemanager::getInstance()->put_contents(
            $this->path, 
            sprintf('The maintenance mode started at %s.'
                . ' Remove this file to disable the maintenance mode manually,'
                . ' but only if it is running for longer than 15 minutes!', $date)
        );

        if ($maintenance_enabled) {
            AutoUpdater_Config::set('maintenance_started_at', $date);
        }

        return $maintenance_enabled;
    }

    public function disable()
    {
        $maintenance_disabled = true;
        $filemanager = AutoUpdater_Filemanager::getInstance();

        if ($filemanager->exists($this->path)) {
            $maintenance_disabled = $filemanager->delete($this->path);
        }

        if ($maintenance_disabled) {
            AutoUpdater_Config::set('maintenance_started_at', null);
        }

        return $maintenance_disabled;
    }
}
