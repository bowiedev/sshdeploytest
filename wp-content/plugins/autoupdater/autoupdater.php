<?php
/**
 * Plugin Name: WP Engine Smart Plugin Manager
 * Plugin URI:
 * Description: WP Engine Smart Plugin Manager simplifies plugin management by automatically updating your plugins every day and ensuring your site continues to work as expected.
 * Version: 2.0.5
 * Text Domain: autoupdater
 * Author: WP Engine
 * Author URI: https://wpengine.com
 * License: GNU/GPL http://www.gnu.org/licenses/gpl-3.0.html
 */

// No direct access
function_exists('add_action') or die;

require_once ABSPATH . 'wp-admin/includes/plugin.php';

if (!defined('AUTOUPDATER_WP_VERSION')) {
    if (!function_exists('AutoUpdater_getWpVersion')) {
        function AutoUpdater_getWpVersion()
        {
            $content = @file_get_contents(ABSPATH . WPINC . '/version.php');
            if ($content && preg_match('/\$wp_version\s*=\s*\'([^\']+)\'/', $content, $match)) {
                return $match[1];
            }
            return $GLOBALS['wp_version'];
        }
    }
    define('AUTOUPDATER_WP_VERSION', AutoUpdater_getWpVersion());
}

if (version_compare(AUTOUPDATER_WP_VERSION, '3.0', '>=') && version_compare(PHP_VERSION, '5.3', '>=')) {
    $data = get_file_data(__FILE__, array('Name' => 'Plugin Name', 'Author' => 'Author', 'Version' => 'Version'));

    define('AUTOUPDATER_WP_PLUGIN_NAME', $data['Name']);
    define('AUTOUPDATER_WP_PLUGIN_AUTHOR', $data['Author']);
    define('AUTOUPDATER_WP_PLUGIN_FILE', __FILE__);
    define('AUTOUPDATER_WP_PLUGIN_PATH', dirname(__FILE__) . '/');
    define('AUTOUPDATER_WP_PLUGIN_SLUG', plugin_basename(__FILE__));
    define('AUTOUPDATER_WP_PLUGIN_BASENAME', basename(__FILE__, '.php'));

    define('AUTOUPDATER_LIB', true);
    define('AUTOUPDATER_CMS', 'wordpress');
    define('AUTOUPDATER_SITE_PATH', rtrim(ABSPATH, '/\\') . '/');
    define('AUTOUPDATER_VERSION', $data['Version']);
    define('AUTOUPDATER_API_HOST', 'au-api.wpesvc.net');

    if (!function_exists('AutoUpdater_getRootPath')) {
        function AutoUpdater_getRootPath()
        {
            if (!empty($_SERVER['SCRIPT_FILENAME'])) {
                $path = dirname(realpath($_SERVER['SCRIPT_FILENAME'])) . '/';
                if (basename($path) == 'wp-admin') {
                    return dirname($path) . '/';
                }

                return $path;
            }

            $files = get_included_files();
            if (isset($files[0]) && substr($files[0], -9) == 'index.php') {
                return dirname(realpath($files[0])) . '/';
            }

            return AUTOUPDATER_SITE_PATH;
        }
    }

    if (!defined('AUTOUPDATER_ROOT_PATH')) {
        define('AUTOUPDATER_ROOT_PATH', AutoUpdater_getRootPath());
    }

    require_once AUTOUPDATER_WP_PLUGIN_PATH . 'lib/Init.php';

    $api = AutoUpdater_Api::getInstance();

    if (is_admin() || $api->isInitialized() || defined('WP_CLI')) {
        require_once AUTOUPDATER_LIB_PATH . 'Installer.php';
        AutoUpdater_Installer::getInstance();
    }

    require_once AUTOUPDATER_WP_PLUGIN_PATH . 'app/Application.php';

    AutoUpdater_WP_Application::getInstance();
} elseif (!function_exists('autoUpdaterRequirementsNotice')) {
    function autoUpdaterRequirementsNotice()
    {
        ?>
        <div class="error">
            <p><?php printf(__('This plugin requires WordPress %s and PHP %s', 'autoupdater'), '3.0+', '5.3+');?></p>
        </div>
        <?php

    }

    add_action('admin_notices', 'autoUpdaterRequirementsNotice');
}
