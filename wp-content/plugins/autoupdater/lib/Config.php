<?php
defined('AUTOUPDATER_LIB') or die;

class AutoUpdater_Config
{
    protected static $instance = null;
    protected $prefix = 'autoupdater_';

    /**
     * @return static
     */
    protected static function getInstance()
    {
        if (!is_null(static::$instance)) {
            return static::$instance;
        }

        $class_name = AutoUpdater_Loader::loadClass('Config');

        static::$instance = new $class_name();

        return static::$instance;
    }

    /**
     * @param string     $key
     * @param null|mixed $default
     *
     * @return mixed
     */
    public static function get($key, $default = null)
    {
        if ($key == 'debug' && defined('AUTOUPDATER_DEBUG') && AUTOUPDATER_DEBUG) {
            return 1;
        }

        return static::getInstance()->getOption($key, $default);
    }

    /**
     * @param string     $key
     * @param mixed|null $default
     *
     * @return mixed
     */
    protected function getOption($key, $default = null)
    {
        $value = get_option($this->prefix . $key, $default);

        if ($key == 'worker_token' && !$value) {
            $value = get_option($this->prefix . 'write_token', $default);
        }

        return $value;
    }

    /**
     * @param string $key
     * @param mixed  $value
     *
     * @return bool
     */
    public static function set($key, $value)
    {
        return static::getInstance()->setOption($key, $value);
    }

    /**
     * @param string $key
     * @param mixed  $value
     *
     * @return bool
     */
    protected function setOption($key, $value)
    {
        // Possible comparison of values string(1) "0" and int(0) so don't use
        // identical operator
        $old_value = get_option($this->prefix . $key, null);
        if ($old_value == $value && !is_null($old_value)) {
            return true;
        }

        return update_option($this->prefix . $key, $value);
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public static function remove($key)
    {
        return static::getInstance()->removeOption($key);
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    protected function removeOption($key)
    {
        return delete_option($this->prefix . $key);
    }

    /**
     * @return bool
     */
    public static function removeAll()
    {
        return static::getInstance()->removeAllOptions();
    }

    /**
     * @return bool
     */
    protected function removeAllOptions()
    {
        global $wpdb;

        $options = $wpdb->get_col('SELECT option_name'
            . ' FROM ' . $wpdb->options
            . ' WHERE option_name LIKE "' . $this->prefix . '%"'
        );

        foreach ($options as $option) {
            delete_option($option);
        }

        return true;
    }

    /**
     * @return string
     */
    public static function getSiteUrl()
    {
        return rtrim(static::getInstance()->getOptionSiteUrl(), '/');
    }

    /**
     * @return string
     */
    protected function getOptionSiteUrl()
    {
        return get_home_url();
    }

    /**
     * @return string
     */
    public static function getSiteBackendUrl()
    {
        return rtrim(static::getInstance()->getOptionSiteBackendUrl(), '/');
    }

    /**
     * @return string
     */
    protected function getOptionSiteBackendUrl()
    {
        return get_admin_url();

    }

    /**
     * @return string
     */
    public static function getSiteLanguage()
    {
        return static::getInstance()->getOptionSiteLanguage();
    }

    /**
     * @return string
     */
    protected function getOptionSiteLanguage()
    {
        return str_replace('_', '-', get_option('WPLANG', defined('WPLANG') && WPLANG ? WPLANG : 'en_US'));
    }

    /**
     * @return string
     */
    public static function getAutoUpdaterUrl()
    {
        // Callback syntax: "subdomain:port:protocol". Example: "app:443:https" or just "app"
        if (!defined('AUTOUPDATER_CALLBACK')) {
            return 'https://' . AUTOUPDATER_API_HOST;
        }

        @list($subdomain, $port, $protocol) = explode(':', AUTOUPDATER_CALLBACK);

        return ($protocol == 'http' ? 'http' : 'https') . '://'
        . $subdomain . '.' . static::$host
            . ($port > 0 ? ':' . (int) $port : '');
    }

    /**
     * @return string
     */
    public static function getAutoUpdaterApiBaseUrl()
    {
        return self::getAutoUpdaterUrl() . '/v2/worker/';
    }

    /**
     * @param bool $force
     * @return bool
     * @throws AutoUpdater_Exception_Response
     */
    public static function loadAutoUpdaterConfigByApi($force = false)
    {
        return static::getInstance()->loadAutoUpdaterConfig($force);
    }

    /**
     * @param bool $force
     *
     * @return bool
     */
    protected function loadAutoUpdaterConfig($force = false)
    {
        if (!$this->getOption('site_id')) {
            return true;
        }

        if (!$force && !defined('AUTOUPDATER_DEBUG') && $this->getOption('config_cached', 0) > strtotime('-1 hour')) {
            return true;
        }

        $response = AutoUpdater_Request::api('get', 'settings');
        if ($response->code !== 200) {
            return false;
        }

        if (!isset($response->body->settings)) {
            return false;
        }
        $settings = $response->body->settings;

        // Auto-Updater state
        if (isset($settings->autoupdater_enabled)) {
            $this->setOption('autoupdater_enabled', (int) $settings->autoupdater_enabled);
        }

        // Updates settings
        if (isset($settings->update_core)) {
            $this->setOption('update_core', (int) $settings->update_core);
        }
        if (isset($settings->update_core_minor_policy)) {
            $this->setOption('update_core_minor_policy', (string) $settings->update_core_minor_policy);
        }
        if (isset($settings->update_plugins)) {
            $this->setOption('update_plugins', (int) $settings->update_plugins);
        }
        if (isset($settings->update_themes)) {
            $this->setOption('update_themes', (int) $settings->update_themes);
        }
        if (isset($settings->excluded_plugins) && is_array($settings->excluded_plugins)) {
            $this->setOption('excluded_plugins', $settings->excluded_plugins);
        }
        if (isset($settings->excluded_themes) && is_array($settings->excluded_themes)) {
            $this->setOption('excluded_themes', $settings->excluded_themes);
        }
        if (isset($settings->autoupdate_at)) {
            $this->setOption('autoupdate_at', (int) $settings->autoupdate_at);
        }
        if (property_exists($settings, 'sitemap_url')) { // sitemap can be null
            $this->setOption('sitemap_url', (string) $settings->sitemap_url);
        }
        if (isset($settings->maintenance_mode)) {
            $this->setOption('maintenance_mode', (int) $settings->maintenance_mode);
        }
        if (isset($settings->auto_rollback)) {
            $this->setOption('auto_rollback', (int) $settings->auto_rollback);
        }
        if (property_exists($settings, 'vrt_css_exclusions')) { // vrt_css_exclusions can be null
            $this->setOption('vrt_css_exclusions', (string) $settings->vrt_css_exclusions);
        }

        // Email address to receive notification
        if (isset($settings->notification_emails)) {
            $this->setOption('notification_emails', implode(', ', (array) $settings->notification_emails));
        }

        if (isset($settings->notification_on_success)) {
            $this->setOption('notification_on_success', (int) $settings->notification_on_success);
        }
        if (isset($settings->notification_on_failure)) {
            $this->setOption('notification_on_failure', (int) $settings->notification_on_failure);
        }

        // Plugin page view
        if (isset($settings->page_disabled_template)) {
            $this->setOption('page_disabled_template', (string) $settings->page_disabled_template);
        }
        if (isset($settings->page_enabled_template)) {
            $this->setOption('page_enabled_template', (string) $settings->page_enabled_template);
        }

        // Save the time when settings were cached
        $this->setOption('config_cached', time());

        return true;
    }

    /**
     * @param array $data
     *
     * @return bool
     */
    public static function saveAutoUpdaterConfigByApi($data)
    {
        return static::getInstance()->saveAutoUpdaterConfig($data);
    }

    /**
     * @param array $data
     *
     * @return bool
     */
    protected function saveAutoUpdaterConfig($data)
    {
        if (!$this->getOption('site_id')) {
            return true;
        }

        $changed = false;
        $settings = array();

        if (array_key_exists('autoupdater_enabled', $data)) {
            $settings['autoupdater_enabled'] = (bool) $data['autoupdater_enabled'];
            if ((int) $this->getOption('autoupdater_enabled') !== (int) $settings['autoupdater_enabled']) {
                $changed = true;
            }
        }
        if (array_key_exists('update_core', $data)) {
            $settings['update_core'] = (bool) $data['update_core'];
            if ((int) $this->getOption('update_core') !== (int) $settings['update_core']) {
                $changed = true;
            }
        }
        if (array_key_exists('update_plugins', $data)) {
            $settings['update_plugins'] = (bool) $data['update_plugins'];
            if ((int) $this->getOption('update_plugins') !== (int) $settings['update_plugins']) {
                $changed = true;
            }
        }
        if (array_key_exists('update_themes', $data)) {
            $settings['update_themes'] = (bool) $data['update_themes'];
            if ((int) $this->getOption('update_themes') !== (int) $settings['update_themes']) {
                $changed = true;
            }
        }
        if (array_key_exists('autoupdate_at', $data)) {
            $settings['autoupdate_at'] = (int) $data['autoupdate_at'];
            if ((int) $this->getOption('autoupdate_at') !== $settings['autoupdate_at']) {
                $changed = true;
            }
        }
        if (array_key_exists('sitemap_url', $data)) {
            // To remove the sitemap URL, provide NULL, not an empty string
            $settings['sitemap_url'] = $data['sitemap_url'] ? (string) $data['sitemap_url'] : null;
            if ((string) $this->getOption('sitemap_url') !== (string) $data['sitemap_url']) {
                $changed = true;
            }
        }
        if (array_key_exists('maintenance_mode', $data)) {
            $settings['maintenance_mode'] = (bool) $data['maintenance_mode'];
            if ((int) $this->getOption('maintenance_mode') !== (int) $settings['maintenance_mode']) {
              $changed = true;
            }
        }
        if (array_key_exists('auto_rollback', $data)) {
            $settings['auto_rollback'] = (bool) $data['auto_rollback'];
            if ((int) $this->getOption('auto_rollback') !== (int) $settings['auto_rollback']) {
                $changed = true;
            }
        }
        if (array_key_exists('notification_emails', $data)) {
            if ((string) $this->getOption('notification_emails') !== (string) $data['notification_emails']) {
                $settings['notification_emails'] = array_map('trim', explode(',', (string) $data['notification_emails']));
                $changed = true;
            }
        }
        if (array_key_exists('notification_on_success', $data)) {
            $settings['notification_on_success'] = (bool) $data['notification_on_success'];
            if ((int) $this->getOption('notification_on_success') !== (int) $settings['notification_on_success']) {
                $changed = true;
            }
        }
        if (array_key_exists('notification_on_failure', $data)) {
            $settings['notification_on_failure'] = (bool) $data['notification_on_failure'];
            if ((int) $this->getOption('notification_on_failure') !== (int) $settings['notification_on_failure']) {
                $changed = true;
            }
        }
        if (array_key_exists('vrt_css_exclusions', $data)) {
            $settings['vrt_css_exclusions'] = $data['vrt_css_exclusions'] ? (string) $data['vrt_css_exclusions'] : null;
            if ((string) $this->getOption('vrt_css_exclusions') !== (string) $data['vrt_css_exclusions']) {
                $changed = true;
            }
        }

        if (array_key_exists('excluded_plugins', $data)) {
            $data['excluded_plugins'] = (array) $data['excluded_plugins'];
            $excluded_plugins = (array) $this->getOption('excluded_plugins', array());

            // Check if number of selected items has change
            if (count($data['excluded_plugins']) !== count($excluded_plugins) ||
                count($data['excluded_plugins']) !== count(array_unique(array_merge($excluded_plugins, $data['excluded_plugins'])))) {
                $changed = true;
            }
            unset($excluded_plugins);

            $settings['excluded_plugins'] = array_unique($data['excluded_plugins']);
        }

        if (array_key_exists('excluded_themes', $data)) {
            $data['excluded_themes'] = (array) $data['excluded_themes'];
            $excluded_themes = (array) $this->getOption('excluded_themes', array());

            if (count($data['excluded_themes']) !== count($excluded_themes) ||
                count($data['excluded_themes']) !== count(array_unique(array_merge($excluded_themes, $data['excluded_themes'])))) {
                $changed = true;

            }
            unset($excluded_themes);

            $settings['excluded_themes'] = array_unique($data['excluded_themes']);
        }

        if (array_key_exists('worker_token', $data)) {
            $settings['worker_token'] = (string) $data['worker_token'];
            if ((string) $this->getOption('worker_token') !== $settings['worker_token']) {
                $changed = true;
            }
        }
        if (array_key_exists('aes_key', $data)) {
            $settings['aes_key'] = (string) $data['aes_key'];
            if ((string) $this->getOption('aes_key') !== $settings['aes_key']) {
                $changed = true;
            }
        }

        if ($changed === false) {
            return true;
        }

        $response = AutoUpdater_Request::api('post', 'settings', $settings);
        if ($response->code === 204) {
            return true;
        }

        return false;
    }
}
