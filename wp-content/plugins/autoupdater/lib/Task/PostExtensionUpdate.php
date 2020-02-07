<?php
defined('AUTOUPDATER_LIB') or die;

class AutoUpdater_Task_PostExtensionUpdate extends AutoUpdater_Task_Base
{
    /**
     * @return array
     * @throws
     */
    public function doTask()
    {
        global $pagenow;
        $pagenow = 'update-core.php';

        $type = strtolower($this->input('type'));
        $slug = strtolower($this->input('slug'));
        $path = $this->input('path');

        if (!$type || (!$path && !$slug && $type != 'translation')) {
            throw new AutoUpdater_Exception_Response('Nothing to update', 400);
        }

        $result = null;
        $response = array(
            'success' => false,
            'message' => 'Failed to update ' . $type . ': ' . $slug,
        );

        $filemanager = AutoUpdater_Filemanager::getInstance();
        if ($path && !preg_match('!^(http|https|ftp)://!i', $path) && !$filemanager->exists($path)) {
            $path = AUTOUPDATER_SITE_PATH . $path;
            if (!$filemanager->exists($path)) {
                throw new AutoUpdater_Exception_Response('Installation path not found', 404);
            }
        }

        $upgrader_path = AUTOUPDATER_LIB_PATH . 'Upgrader/';
        require_once $upgrader_path . 'Dependencies.php';

        AutoUpdater_Loader::loadClass('Helper_Version');

        $old_version = $new_version = null;
        $expected_version = $this->input('version');

        switch ($type) {
            case 'core':
                AutoUpdater_Log::debug('Starting to update WordPress');

                require_once $upgrader_path . 'Core.php';
                require_once $upgrader_path . 'Skin/Core.php';

                $wp_upgrade_dir = WP_CONTENT_DIR . '/upgrade';
                if (!$filemanager->is_dir($wp_upgrade_dir)) {
                    $filemanager->mkdir($wp_upgrade_dir);
                }

                if (empty($expected_version)) {
                    $expected_version = AUTOUPDATER_WP_VERSION;
                }
                if (substr($expected_version, -2) == '.0') {
                    // Remove the last zero from the version X.Y.0
                    $expected_version = substr($expected_version, 0, -2);
                }

                $working_dir = $this->input('path');
                $update = (object) array(
                    'response' => 'upgrade',
                    'download' => $working_dir,
                    'locale' => 'en_US',
                    'package' => $working_dir,
                    /** @since 3.2.0 */
                    'packages' => (object) array(
                        'full' => false,
                        'no_content' => $working_dir,
                        'new_bundled' => false,
                        'partial' => false,
                        'rollback' => false,
                    ),
                    'current' => $expected_version,
                    'version' => $expected_version,
                    'php_version' => '5.2.4',
                    'mysql_version' => '5.0',
                    'new_bundled' => false,
                    'partial_version' => false,
                );

                ob_start();
                $upgrader = new AutoUpdater_Upgrader_Core(
                    new AutoUpdater_Upgrader_Skin_Core()
                );
                $result = $upgrader->upgrade($update, array('pre_check_md5' => false));
                $output = ob_get_clean();

                // returns string with a new version or null on success
                if (is_string($result) && preg_match('/^\d+(\.\d+)+/', $result)) {
                    /** @since 3.3.0 */
                    // Check if the version after update is the same or higher than expected
                    $result = version_compare(AutoUpdater_Helper_Version::fixAndFormat($expected_version), AutoUpdater_Helper_Version::fixAndFormat($result)) <= 0;
                    if ($result === false) {
                        $result = new WP_Error('wrong_version', sprintf('Expected version: %s, current version: %s', $expected_version, $result));
                    }
                }
                break;

            case 'plugin':
                AutoUpdater_Log::debug('Starting to update plugin: ' . $slug);

                require_once $upgrader_path . 'Plugin.php';
                require_once $upgrader_path . 'Skin/Plugin.php';

                if (!$path && !get_site_transient('update_plugins')) {
                    // do it two times, so all data will be correctly filled
                    wp_update_plugins();
                    wp_update_plugins();
                }

                if (!$path && strpos($slug, 'masterslider.php') !== false) {
                    // prepare update of exceptional plugins
                    if (!class_exists(AutoUpdater_Loader::getClassPrefix() . 'Helper_Extension')) {
                        require_once AUTOUPDATER_LIB_PATH . 'Helper/Extension.php';
                    }
                    AutoUpdater_Helper_Extension::loadMasterSliderPro();
                }

                $plugin_path = WP_PLUGIN_DIR . '/' . $slug;
                if (!$filemanager->exists($plugin_path)) {
                    $slug = $this->getPluginRealSlug($slug);
                    if (!$slug) {
                        throw AutoUpdater_Exception_Response::getException(
                            200,
                            $response['message'],
                            'no_update_warning',
                            'No update was performed, plugin directory not found'
                        );
                    }
                    $plugin_path = WP_PLUGIN_DIR . '/' . $slug;
                }

                $data = get_file_data($plugin_path, array('Version' => 'Version'));
                $old_version = $data['Version'];

                if ($path) {
                    $nonce = 'plugin-upload';
                    $url = add_query_arg(array('package' => $path), 'update.php?action=upload-plugin');
                    $type = 'upload'; //Install plugin type, From Web or an Upload.
                } else {
                    $plugin = $slug;
                    $nonce = 'upgrade-plugin_' . $plugin;
                    $url = 'update.php?action=upgrade-plugin&plugin=' . urlencode($plugin);

                    $this->logInAdmin();
                }

                ob_start();
                $upgrader = new AutoUpdater_Upgrader_Plugin(
                    new AutoUpdater_Upgrader_Skin_Plugin(
                        compact('nonce', 'url', 'plugin', 'type')
                    )
                );
                // don't clear update cache, so next plugin's update step in same action will be able to use update cache data
                $result = $path ? $upgrader->install($path, array('clear_update_cache' => false)) : $upgrader->upgrade($slug, array('clear_update_cache' => false));
                $output = ob_get_clean();

                $data = get_file_data($plugin_path, array('Version' => 'Version'));
                $new_version = $data['Version'];
                break;

            case 'theme':
                AutoUpdater_Log::debug('Starting to update theme: ' . $slug);

                require_once $upgrader_path . 'Theme.php';
                require_once $upgrader_path . 'Skin/Theme.php';

                if (!$path && !get_site_transient('update_themes')) {
                    // do it two times, so all data will be correctly filled
                    wp_update_themes();
                    wp_update_themes();
                }

                $theme_path = WP_CONTENT_DIR . '/themes/' . $slug . '/style.css';
                if (!$filemanager->exists($theme_path)) {
                    $theme_path = $this->getThemeRealPath($slug);
                    if (!$theme_path) {
                        throw AutoUpdater_Exception_Response::getException(
                            200,
                            $response['message'],
                            'no_update_warning',
                            'No update was performed, theme directory not found'
                        );
                    }
                }

                $data = get_file_data($theme_path, array('Version' => 'Version'));
                $old_version = $data['Version'];

                if ($path) {
                    $nonce = 'theme-upload';
                    $url = add_query_arg(array('package' => $path), 'update.php?action=upload-theme');
                    $type = 'upload'; //Install theme type, From Web or an Upload.
                } else {
                    $theme = $slug;
                    $nonce = 'upgrade-theme_' . $theme;
                    $url = 'update.php?action=upgrade-theme&theme=' . urlencode($theme);

                    $this->logInAdmin();
                }

                ob_start();
                $upgrader = new AutoUpdater_Upgrader_Theme(
                    new AutoUpdater_Upgrader_Skin_Theme(
                        compact('nonce', 'url', 'theme', 'type')
                    )
                );
                // don't clear update cache, so next theme's update step in same action will be able to use update cache data
                $result = $path ? $upgrader->install($path, array('clear_update_cache' => false)) : $upgrader->upgrade($slug, array('clear_update_cache' => false));
                $output = ob_get_clean();

                $data = get_file_data($theme_path, array('Version' => 'Version'));
                $new_version = $data['Version'];
                break;

            case 'translation':
                AutoUpdater_Log::debug('Starting to update translations');

                // Language_Pack_Upgrader skin was introduced in 3.7 so...
                if (version_compare(AUTOUPDATER_WP_VERSION, '3.7', '>=')) {
                    require_once $upgrader_path . 'Skin/Languagepack.php';

                    $url = 'update-core.php?action=do-translation-upgrade';
                    $nonce = 'upgrade-translations';
                    $context = WP_LANG_DIR;

                    ob_start();
                    $upgrader = new Language_Pack_Upgrader(
                        new AutoUpdater_Upgrader_Skin_Languagepack(
                            compact('url', 'nonce', 'context')
                        )
                    );
                    // don't clear update cache, so next extension's update step in same action will be able to use update cache data
                    $result = $upgrader->bulk_upgrade(array(), array('clear_update_cache' => false));
                    $output = ob_get_clean();

                    // returns an array of results on success, or true if there are no updates
                    if (is_array($result)) {
                        $result = true;
                    } elseif ($result === true) {
                        $result = new WP_Error('up_to_date', 'There are no translations updates');
                    }

                    /** @see AutoUpdater_Upgrader_Skin_Languagepack::get_translations() */
                    $translations = $upgrader->skin->get_translations();
                    if (!empty($translations)) {
                        $response['translations'] = $translations;
                    }
                } else {
                    $result = true;
                }
        }

        $filemanager->clearPhpCache();

        /** @see AutoUpdater_Upgrader_Skin_Core::get_errors() */
        $errors = isset($upgrader) ? $upgrader->skin->get_errors() : array();
        if (is_wp_error($result)) {
            /** @var WP_Error $result */
            $errors[$result->get_error_code()] = $result->get_error_message();
            $result = false;
        }

        if (array_key_exists('up_to_date', $errors) && (in_array($type, array('core', 'translation')) ||
            $expected_version && 
            version_compare(AutoUpdater_Helper_Version::fixAndFormat($expected_version), AutoUpdater_Helper_Version::fixAndFormat($new_version), '<='))) {
            $response['success'] = true;
            $response['message'] = $errors['up_to_date'] != 'up_to_date' ? $errors['up_to_date'] : 'Up-to-date';
            return $response;
        } elseif (array_key_exists('no_package', $errors)) {
            $result = false;
            $response['error'] = array(
                'code' => 'no_package_warning',
                'message' => $errors['no_package'],
            );
            unset($errors['no_package']);
        } elseif (in_array($type, array('plugin', 'theme')) && ($expected_version && 
            version_compare(AutoUpdater_Helper_Version::fixAndFormat($expected_version), AutoUpdater_Helper_Version::fixAndFormat($new_version), '>') ||
            !$expected_version && 
            version_compare(AutoUpdater_Helper_Version::fixAndFormat($old_version), AutoUpdater_Helper_Version::fixAndFormat($new_version), '='))) {
            $result = false;
            $response['error'] = array(
                'code' => 'no_update_warning',
                'message' => 'No update was performed, current version: ' . $new_version
                    . ', expected version: ' . $expected_version,
            );
        }

        if ($result === true || is_null($result)) {
            $response['success'] = true;
            unset($response['message']);
            return $response;
        } elseif (!is_null($result) && !is_bool($result)) {
            $errors['unknown_error'] = 'Result dump: ' . var_export($result, true);
        }

        if (count($errors)) {
            if (!isset($response['error'])) {
                end($errors);
                $response['error'] = array(
                    'code' => key($errors),
                    'message' => current($errors),
                );
                unset($errors[$response['error']['code']]);
            }
            if (count($errors)) {
                $response['errors'] = $errors;
            }
        }

        if (!empty($output)) {
            AutoUpdater_Log::debug('Updater output: ' . $output);
        }

        return $response;
    }

    /**
     * @param string $slug
     *
     * @return string|null
     */
    protected function getPluginRealSlug($slug)
    {
        $plugin_dir_paths = glob(WP_PLUGIN_DIR . '/*');
        foreach ($plugin_dir_paths as $dir_path) {
            $dir = basename($dir_path);

            // Single file plugin
            if (strpos($slug, '/') === false) {
                if (strtolower($dir) === $slug) {
                    return $dir;
                }
                continue;
            }

            // Plugin in directory
            if (strtolower($dir) === dirname($slug)) {
                $plugin_file_paths = glob(WP_PLUGIN_DIR . '/' . $dir . '/*.php');
                foreach ($plugin_file_paths as $file_path) {
                    $file = basename($file_path);
                    $slug_file = basename($slug);
                    if (strtolower($file) === $slug_file) {
                        return $dir . '/' . $file;
                    }
                    continue;
                }
            }
        }

        return null;
    }

    /**
     * @param string $slug
     *
     * @return string|null
     */
    protected function getThemeRealPath(&$slug)
    {
        // Theme in directory: wp-themes/slug
        $files = glob(WP_CONTENT_DIR . '/themes/*/style.css');
        foreach ($files as $file_path) {
            $slug_based_on_file = basename(dirname($file_path));
            // Is directory before style.css file the same as slug?
            if (strtolower($slug_based_on_file) === $slug) {
                $slug = $slug_based_on_file;
                return $file_path;
            }
        }

        // Theme in subdirectory: wp-themes/slug-1.0.0/slug
        $files = glob(WP_CONTENT_DIR . '/themes/*/*/style.css');
        foreach ($files as $file_path) {
            $slug_based_on_file = basename(dirname($file_path));
            // Is directory before style.css file the same as slug?
            if (strtolower($slug_based_on_file) === $slug) {
                $slug = $slug_based_on_file;
                return $file_path;
            }
        }

        return null;
    }

    /**
     * @return bool
     */
    protected function logInAdmin()
    {
        $users = get_users(array('role' => 'administrator', 'number' => 1));
        if (!empty($users[0]->ID)) {
            require_once ABSPATH . 'wp-includes/pluggable.php';
            wp_set_current_user($users[0]->ID);
        }

        return is_user_logged_in();
    }
}
