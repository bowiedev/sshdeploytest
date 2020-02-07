<?php
defined('AUTOUPDATER_LIB') or die;

if (!empty($_REQUEST['wpe_callback'])) {
    // callback syntax: "subdomain:port:protocol". Example: "app:443:https" or just "app"
    define('AUTOUPDATER_CALLBACK', preg_replace('/[^a-z0-9:]/', '', $_REQUEST['wpe_callback']));
}

if (!defined('AUTOUPDATER_API_HOST')) {
    define('AUTOUPDATER_API_HOST', 'localhost');
}
if (!defined('AUTOUPDATER_VERSION')) {
    define('AUTOUPDATER_VERSION', '2.0');
}
if (!defined('AUTOUPDATER_SITE_PATH')) {
    define('AUTOUPDATER_SITE_PATH', dirname(dirname(__FILE__)) . '/');
}

if (!defined('AUTOUPDATER_ROOT_PATH')) {
    define('AUTOUPDATER_ROOT_PATH', AUTOUPDATER_SITE_PATH);
}

if (preg_match('/-(staging|dev)\./', AUTOUPDATER_API_HOST) && !defined('AUTOUPDATER_DEBUG')) {
    define('AUTOUPDATER_DEBUG', true);
}

if (!defined('AUTOUPDATER_LIB_PATH')) {
    define('AUTOUPDATER_LIB_PATH', dirname(__FILE__) . '/');

    require_once AUTOUPDATER_LIB_PATH . 'Loader.php';
    require_once AUTOUPDATER_LIB_PATH . 'Config.php';

    require_once AUTOUPDATER_LIB_PATH . 'Api.php';
    require_once AUTOUPDATER_LIB_PATH . 'Authentication.php';
    require_once AUTOUPDATER_LIB_PATH . 'Backuptool.php';
    require_once AUTOUPDATER_LIB_PATH . 'Db.php';
    require_once AUTOUPDATER_LIB_PATH . 'Filemanager.php';
    require_once AUTOUPDATER_LIB_PATH . 'Log.php';
    require_once AUTOUPDATER_LIB_PATH . 'Maintenance.php';
    require_once AUTOUPDATER_LIB_PATH . 'Request.php';
    require_once AUTOUPDATER_LIB_PATH . 'Response.php';
    require_once AUTOUPDATER_LIB_PATH . 'Task.php';

    require_once AUTOUPDATER_LIB_PATH . 'Exception/Response.php';

    if (file_exists(AUTOUPDATER_LIB_PATH . 'vendor/autoload.php')) {
        require_once AUTOUPDATER_LIB_PATH . 'vendor/autoload.php';
    }

    if (defined('WP_CLI')) {
        require_once AUTOUPDATER_LIB_PATH . 'Cli.php';
    }
}
