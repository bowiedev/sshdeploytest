<?php
defined('AUTOUPDATER_LIB') or die;

class AutoUpdater_Backuptool
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

        $class_name = AutoUpdater_Loader::loadClass('Backuptool');

        static::$instance = new $class_name();

        return static::$instance;
    }

    /**
     * @return null|string
     */
    public function getDir()
    {
        return AutoUpdater_Config::get('backuptool_dir');
    }

    /**
     * @return null|string
     */
    public function getPath()
    {
        if (!($backuptool_dir = $this->getDir())) {
            return null;
        }

        if (!is_dir(AUTOUPDATER_ROOT_PATH . $backuptool_dir)
            && is_dir(AUTOUPDATER_ROOT_PATH . '../' . $backuptool_dir)) {
            // Backup Tool might be one level above the website
            return dirname(AUTOUPDATER_ROOT_PATH) . '/' . $backuptool_dir . '/';
        }

        return AUTOUPDATER_ROOT_PATH . $backuptool_dir . '/';
    }

    /**
     * @return null|string
     */
    public function getUrl()
    {
        if (!($backuptool_dir = $this->getDir())) {
            return null;
        }

        return AutoUpdater_Config::getSiteUrl() . '/' . $backuptool_dir;
    }

    /**
     * @return array
     */
    protected function getConfig()
    {
        $config_file = $this->getPath() . 'Solo/assets/private/config.php';
        $filemanager = AutoUpdater_Filemanager::getInstance();

        if ($filemanager->exists($config_file) && ($config = $filemanager->get_contents($config_file))) {
            list(, $config) = explode("\n", $config, 2);

            try
            {
                return json_decode($config, true);
            } catch (Exception $e) {

            }
        }

        return array();
    }

    /**
     * @return string
     */
    public function getDbPrefix()
    {
        $config = $this->getConfig();

        return !empty($config['prefix']) ? $config['prefix'] : '';
    }

    /**
     * @return string
     */
    public function getSecretWord()
    {
        $config = $this->getConfig();

        return !empty($config['options']['frontend_secret_word']) ? $config['options']['frontend_secret_word'] : '';
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        include_once $this->getPath() . 'version.php';

        if (defined('AKEEBABACKUP_VERSION')) {
            return AKEEBABACKUP_VERSION;
        } elseif (defined('AKEEBA_VERSION')) {
            return AKEEBA_VERSION;
        }

        return '0.0.0';
    }

    /**
     * @param bool $htaccess_disable
     */
    protected function setWAFExceptions($htaccess_disable = false)
    {
        // Flush htaccess rules to add a new rule for the backup tool directory.
        global $wp_rewrite;
        $wp_rewrite->flush_rules();

        $filemanager = AutoUpdater_Filemanager::getInstance();
        $path = $this->getPath();

        if ($htaccess_disable === true) {
            $htaccess_file = $path . '.htaccess';

            if ($filemanager->is_file($htaccess_file)) {
                $filemanager->move($htaccess_file, $path . '.htaccess.disable');
            }
        } else {
            // For apache servers - set htaccess.txt in backup tool dir to .htaccess
            $server_software = isset($_SERVER['SERVER_SOFTWARE']) ? strtolower($_SERVER['SERVER_SOFTWARE']) : false;
            $htaccess_file = $path . 'htaccess.txt';
            $htaccess_disabled_file = $path . '.htaccess.disable';

            if ($server_software &&
                strpos($server_software, 'apache') !== false &&
                $filemanager->is_file($htaccess_file) &&
                !$filemanager->is_file($htaccess_disabled_file)) {
                $filemanager->move($htaccess_file, $path . '.htaccess');
            }
        }
    }

    /**
     * @return bool|null
     */
    public function uninstall()
    {
        if (!($backuptool_path = $this->getPath())) {
            return null;
        }

        AutoUpdater_Log::debug('Uninstalling Backup Tool');

        if ($prefix = $this->getDbPrefix()) {
            AutoUpdater_Db::getInstance()->doQuery(
                'DROP TABLE IF EXISTS'
                . ' `' . $prefix . 'akeeba_common`'
                . ', `' . $prefix . 'ak_common`'
                . ', `' . $prefix . 'ak_params`'
                . ', `' . $prefix . 'ak_profiles`'
                . ', `' . $prefix . 'ak_stats`'
                . ', `' . $prefix . 'ak_storage`'
                . ', `' . $prefix . 'ak_users`'
            );
        }

        return AutoUpdater_Filemanager::getInstance()
            ->delete($backuptool_path, true);
    }

    /**
     * @return string
     */
    protected function getInstallerName()
    {
        return 'angie-wordpress';
    }

    /**
     * @param null|int    $id
     * @param null|string $filename
     *
     * @return string
     */
    protected function getBackupSql($id = null, $filename = null)
    {
        $prefix = $this->getDbPrefix();

        $sql = 'SELECT `id`, `archivename`, `multipart`, `total_size`'
            . ' FROM `' . $prefix . 'ak_stats`'
            . ' WHERE';
        if ($id) {
            $sql .= ' `id` = ' . (int) $id;
        } elseif ($filename) {
            $sql .= ' `archivename` = "' . addslashes($filename) . '"';
        } else {
            $sql .= ' `archivename` != "" AND `archivename` IS NOT NULL';
        }
        $sql .= ' ORDER BY `id` DESC'
            . ' LIMIT 0,10';

        return $sql;
    }

    /**
     * Get the latest existing backup or the one given by the ID or filename
     *
     * @param null|int    $id
     * @param null|string $filename
     *
     * @return null|array
     */
    public function getBackup($id = null, $filename = null)
    {
        $sql = $this->getBackupSql($id, $filename);
        $backups = AutoUpdater_Db::getInstance()
            ->doQueryWithResults($sql);

        if (empty($backups)) {
            return null;
        }

        $backups_path = $this->getPath() . 'backups/';
        $filemanager = AutoUpdater_Filemanager::getInstance();
        $item = null;

        foreach ($backups as $backup) {
            if (empty($backup['archivename'])) {
                continue;
            }

            $backup['archivename'] = basename($backup['archivename']);
            $backup['path'] = $backups_path . $backup['archivename'];
            $backup['exists'] = $filemanager->is_file($backup['path']);
            $backup['multipart'] = $backup['multipart'] > 1 ? $backup['multipart'] : 0;

            // Get the latest existing backup or the one given by the ID or filename
            if ($backup['exists'] || !empty($id) || !empty($filename)) {
                $item = $backup;
                break;
            }
        }

        return $item;
    }

    /**
     * @param string $installation_dir
     * @param string $login
     * @param string $password
     * @param string $secret
     * @param int    $max_backup_id
     * @param array  $options
     *
     * @return array
     *
     * @throws Exception
     */
    public function install($installation_dir, $login, $password, $secret, $max_backup_id, $options = array())
    {
        if (version_compare(PHP_VERSION, '5.3', '<')) {
            return array(
                'success' => false,
                'message' => 'PHP 5.3 is required to install Backup Tool. Server PHP version is ' . PHP_VERSION,
            );
        }

        $backuptool_path = $this->getPath();
        $filemanager = AutoUpdater_Filemanager::getInstance();

        // Check if Backup Tool is already installed.
        if (!empty($backuptool_path) && $filemanager->exists($backuptool_path . 'index.php')) {
            $backuptool_dir = $this->getDir();
            // Check if Backup Tool was installed in another directory
            if ($installation_dir != $backuptool_dir) {
                $filemanager->move($backuptool_path, AUTOUPDATER_ROOT_PATH . $installation_dir);
            }
        }

        // Download a new package
        if (!empty($options['download_url'])) {
            $download_url = $options['download_url'];
        } else {
            $site_id = !empty($options['site_id']) ? (int)$options['site_id'] : 0;
            $download_url = AutoUpdater_Request::getApiUrl('get', 'download/backuptool.zip', null, $site_id);
        }
        $file_path = $filemanager->download($download_url);

        // Unpack the downloaded file
        $backuptool_path = AUTOUPDATER_ROOT_PATH . $installation_dir . '/';
        try
        {
            $filemanager->unpack($file_path, $backuptool_path);
        } catch (Exception $e) {
            // Throw the exception after deleting the downloaded file
            $exception = $e;
        }

        try
        {
            // Delete the downloaded file
            $filemanager->delete($file_path);
        } catch (Exception $e) {

        }

        if (isset($exception)) {
            // Throw the exception with the unpack error
            throw $exception;
        }

        return $this->setup($installation_dir, $login, $password, $secret, $max_backup_id, $options);
    }

    /**
     * @param string      $backup_dir
     * @param null|string $login
     * @param null|string $password
     * @param null|string $secret
     * @param int         $max_backup_id
     * @param array       $options
     * - bool htaccess_disable
     * - int backup_part_size
     *
     * @return array
     */
    public function setup($backup_dir, $login = null, $password = null, $secret = null, $max_backup_id = 0, $options = array())
    {
        if (version_compare(PHP_VERSION, '5.3', '<')) {
            return array(
                'success' => false,
                'message' => 'PHP 5.3 is required to install Backup Tool. Server PHP version is ' . PHP_VERSION,
            );
        }

        if ($backup_dir) {
            $backuptool_path = AUTOUPDATER_ROOT_PATH . $backup_dir . '/';
        } else {
            $backuptool_path = $this->getPath();
            $backup_dir = $this->getDir();
        }

        $filemanager = AutoUpdater_Filemanager::getInstance();
        if (!$filemanager->exists($backuptool_path . 'Awf/Autoloader/Autoloader.php') ||
            false === (include_once $backuptool_path . 'Awf/Autoloader/Autoloader.php')
        ) {
            return array('success' => false, 'message' => 'include_autoloader_error');
        }

        if (!defined('APATH_BASE') &&
            (!$filemanager->exists($backuptool_path . 'defines.php') ||
                false === (include_once $backuptool_path . 'defines.php'))
        ) {
            return array('success' => false, 'message' => 'include_defines_error');
        }

        $autoloader_class = 'Awf\Autoloader\Autoloader';
        $application_class = 'Awf\Application\Application';
        $platform_class = 'Akeeba\Engine\Platform';
        $factory_class = 'Akeeba\Engine\Factory';
        $setup_class = 'Solo\Model\Setup';
        $solo_main_class = 'Solo\Model\Main';

        $prefixes = $autoloader_class::getInstance()->getPrefixes();
        if (!array_key_exists('Solo\\', $prefixes)) {
            $autoloader_class::getInstance()->addMap('Solo\\', APATH_BASE . '/Solo');
        }

        if (!defined('AKEEBAENGINE')) {
            define('AKEEBAENGINE', 1);

            if (!$filemanager->exists($backuptool_path . 'Solo/engine/Factory.php') ||
                false == include_once $backuptool_path . 'Solo/engine/Factory.php'
            ) {
                return array('success' => false, 'message' => 'include_engine_factory_error');
            }

            if (!$filemanager->exists($backuptool_path . 'Solo/alice/factory.php') ||
                false == include_once $backuptool_path . 'Solo/alice/factory.php'
            ) {
                return array('success' => false, 'message' => 'include_alice_factory_error');
            }

            $platform_class::addPlatform('Solo', $backuptool_path . 'Solo/Platform/Solo');
            $platform_class::getInstance()->load_version_defines();
            $platform_class::getInstance()->apply_quirk_definitions();
        }

        // Create the container if it doesn't already exist
        if (!isset($application)) {
            $application = $application_class::getInstance('Solo');
        }

        // Initialise the application
        $application->initialise();
        $container = $application->getContainer();
        $setup = new $setup_class();

        $db_config = AutoUpdater_Db::getInstance()->getConfig();

        // mysql driver (mysql_connect) was removed in PHP 7.0.0
        if ($db_config['driver'] === 'mysql' && version_compare(PHP_VERSION, '7.0.0', '>=')) {
            $db_config['driver'] = 'mysqli';
        }

        if (!($db_prefix = $this->getDbPrefix())) {
            $db_prefix = 'a' . substr($filemanager->getRandomName(), 0, 5) . '_autoupdater_';
        }

        $session = $container->segment;

        $session->set('db_driver', $db_config['driver']);
        $session->set('db_host', $db_config['host']);
        $session->set('db_user', $db_config['user']);
        $session->set('db_pass', $db_config['password']);
        $session->set('db_name', $db_config['name']);
        $session->set('db_prefix', $db_prefix);

        $setup->applyDatabaseParameters();

        $db_exists = AutoUpdater_Db::getInstance()->doQueryWithResults(
            'SHOW TABLES LIKE "' . $db_prefix . 'ak_profiles"'
        );
        if (empty($db_exists)) {
            $setup->installDatabase();
        }

        $live_site = AutoUpdater_Config::getSiteUrl() . '/' . $backup_dir;

        $session->set('setup_timezone', 'UTC');
        $session->set('setup_live_site', $live_site);
        $session->set('setup_session_timeout', 1440);

        if ($login && $password) {
            $session->set('setup_user_username', $login);
            $session->set('setup_user_password', $password);
            $session->set('setup_user_password2', $password);
            $session->set('setup_user_email', 'noreply@wpengine.com');
            $session->set('setup_user_name', 'AutoUpdater');
        }

        // Apply configuration settings to app config
        $setup->setSetupParameters();

        // Try to create the new admin user and log them in
        if ($login && $password) {
            $setup->createAdminUser();
        }

        // Set akeeba system configuration
        if ($secret) {
            $container->appConfig->set('options.frontend_enable', true);
            $container->appConfig->set('options.frontend_secret_word', $secret);
        } else {
            $secret = $container->appConfig->get('options.frontend_secret_word', null);
        }
        $container->appConfig->set('stats_enabled', 0);
        $container->appConfig->set('useencryption', 1);
        $container->appConfig->set('options.frontend_email_on_finish', false);
        $container->appConfig->set('options.displayphpwarning', false);
        $container->appConfig->set('options.siteurl', $live_site . '/');
        $container->appConfig->set('options.confwiz_upgrade', 0);
        $container->appConfig->set('mail.online', false);

        $container->appConfig->saveConfiguration();
        //Generate the secret key if needed
        $solo = new $solo_main_class();
        $solo->checkEngineSettingsEncryption();

        // Configuration Wizard
        $siteParams = array();
        $siteParams['akeeba.basic.output_directory'] = '[DEFAULT_OUTPUT]';
        $siteParams['akeeba.basic.log_level'] = 1;
        $siteParams['akeeba.platform.site_url'] = AutoUpdater_Config::getSiteUrl();
        $siteParams['akeeba.platform.newroot'] = AUTOUPDATER_ROOT_PATH;
        $siteParams['akeeba.platform.dbdriver'] = $db_config['driver'];
        $siteParams['akeeba.platform.dbhost'] = $db_config['host'];
        $siteParams['akeeba.platform.dbusername'] = $db_config['user'];
        $siteParams['akeeba.platform.dbpassword'] = $db_config['password'];
        $siteParams['akeeba.platform.dbname'] = $db_config['name'];
        $siteParams['akeeba.platform.dbprefix'] = $db_config['prefix'];
        $siteParams['akeeba.platform.override_root'] = 1;
        $siteParams['akeeba.platform.override_db'] = 1;
        $siteParams['akeeba.platform.addsolo'] = 0;
        $siteParams['akeeba.platform.scripttype'] = AUTOUPDATER_CMS;
        $siteParams['akeeba.advanced.embedded_installer'] = $this->getInstallerName();
        $siteParams['akeeba.advanced.virtual_folder'] = 'external_files';
        $siteParams['akeeba.advanced.uploadkickstart'] = 0;
        $siteParams['akeeba.quota.enable_count_quota'] = 0;
        if (!empty($options['backup_part_size'])) {
            $siteParams['engine.archiver.common.part_size'] = $options['backup_part_size'];
        } else {
            $siteParams['engine.archiver.common.part_size'] = '104857600';
        }

        $config = $factory_class::getConfiguration();

        $protectedKeys = $config->getProtectedKeys();
        $config->setProtectedKeys(array());

        foreach ($siteParams as $k => $v) {
            $config->set($k, $v);
        }

        $platform_class::getInstance()->save_configuration();

        $config->setProtectedKeys($protectedKeys);
        // End Configuration Wizard.

        AutoUpdater_Config::set('backuptool_dir', $backup_dir);

        $this->setWAFExceptions(isset($options['htaccess_disable']) ? (bool) $options['htaccess_disable'] : false);

        if ($max_backup_id) {
            $max_backup_id = (int) $max_backup_id;
            $auto_increment = (int) AutoUpdater_Db::getInstance()->doQueryWithResults(
                'SELECT `AUTO_INCREMENT` FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = "' . $db_config['name'] . '" AND TABLE_NAME = "' . $db_prefix . 'ak_stats"'
            );

            if ($auto_increment <= $max_backup_id) {
                if (!AutoUpdater_Db::getInstance()->doQuery('ALTER TABLE ' . $db_prefix . 'ak_stats AUTO_INCREMENT=' . ($max_backup_id + 1))) {
                    return array('success' => false, 'message' => 'auto_increment_value_error');
                }
            }
        }

        return array(
            'success' => true,
            'version' => $this->getVersion(),
        );
    }
}
