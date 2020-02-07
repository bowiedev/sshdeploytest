<?php
function_exists('add_action') or die;

class AutoUpdater_WP_Admin
{
    protected static $instance = null;
    protected $menu_slug = 'autoupdater';

    /**
     * @return static
     */
    public static function getInstance()
    {
        if (!is_null(static::$instance)) {
            return static::$instance;
        }

        static::$instance = new AutoUpdater_WP_Admin();

        return static::$instance;
    }

    public function __construct()
    {
        if (is_admin()) {
            if (defined('DOING_AJAX')) {
                add_action('wp_ajax_autoupdater_save', array($this, 'ajaxSaveConfiguration'));
            } else {
                $whitelabelling = AutoUpdater_WP_Whitelabelling::getInstance();
                $this->menu_slug = $whitelabelling->getWhiteLabeledSlug();
                if (!$whitelabelling->isPluginHidden()) {
                    add_action('admin_init', array($this, 'maintenanceOff'));
                    add_action('admin_init', array($this, 'redirectToConfigurationPage'));
                    add_action('admin_init', array($this, 'addMediaFiles'));
                    add_action('admin_menu', array($this, 'addMenuEntry'));
                }
            }
        }
    }

    /**
     * Add menu entry with plug-in settings page.
     */
    public function addMenuEntry()
    {
        $name = AutoUpdater_WP_Whitelabelling::getInstance()
            ->getWhiteLabeledName(AUTOUPDATER_WP_PLUGIN_NAME);

        add_management_page(
            $name,
            $name,
            'manage_options',
            $this->menu_slug,
            array($this, 'displayConfigurationPage')
        );

        if ($this->menu_slug != 'autoupdater') {
            add_submenu_page(
                null,
                $name,
                $name,
                'manage_options',
                'autoupdater',
                array($this, 'displayConfigurationPage')
            );
        };

        // Display "Turn off maintenance" in admin menu if it is enabled for longer than 15 minutes or for an unknown time
        if (!AutoUpdater_Maintenance::getInstance()->isEnabled()) {
            return;
        }

        $date = AutoUpdater_Config::get('maintenance_started_at');
        if ($date) {
            $date = new DateTime($date);
            if ((time() - $date->getTimestamp()) / 60 < 15) {
                return;
            }
        }

        add_menu_page(
            'Turn off maintenance',
            'Turn off maintenance',
            'manage_options',
            admin_url('tools.php?page=autoupdater-maintenance-off'),
            '',
            'dashicons-admin-site',
            0
        );

        add_submenu_page(
            null,
            'Turning off maintenance',
            'Turning off maintenance',
            'manage_options',
            'autoupdater-maintenance-off'
        );
    }

    public function addMediaFiles()
    {
        global $pagenow;

        if ($pagenow != 'tools.php' || !isset($_GET['page']) || $_GET['page'] != $this->menu_slug) {
            return;
        }

        wp_register_style(
            'autoupdater-style',
            plugins_url('media/css/style.css', AUTOUPDATER_WP_PLUGIN_FILE),
            array(),
            AUTOUPDATER_VERSION
        );
        wp_enqueue_style('autoupdater-style');

        wp_register_script(
            'autoupdater-script',
            plugins_url('media/js/script.js', AUTOUPDATER_WP_PLUGIN_FILE),
            array('jquery'),
            AUTOUPDATER_VERSION
        );
        wp_enqueue_script('autoupdater-script');
    }

    public function maintenanceOff()
    {
        global $pagenow;

        if ($pagenow != 'tools.php' || !isset($_GET['page']) || $_GET['page'] != 'autoupdater-maintenance-off') {
            return;
        }

        AutoUpdater_Maintenance::getInstance()->disable();

        wp_redirect(admin_url(), 302);
        exit;
    }

    /**
     * Redirects wp-admin/tools.php?page=autoupdater to the configuration page with the white labelled menu slug
     */
    public function redirectToConfigurationPage()
    {
        global $pagenow;

        if ($pagenow != 'tools.php' || !isset($_GET['page']) || $_GET['page'] == $this->menu_slug || $_GET['page'] != 'autoupdater') {
            return;
        }

        wp_redirect(menu_page_url($this->menu_slug, false), 301);
        exit;
    }

    public function displayConfigurationPage()
    {
        global $user_email;

        AutoUpdater_Config::loadAutoUpdaterConfigByApi();

        $autoupdater_enabled = (int) AutoUpdater_Config::get('autoupdater_enabled');
        $update_core = (int) AutoUpdater_Config::get('update_core', 0);
        $update_core_minor_policy = (string) AutoUpdater_Config::get('update_core_minor_policy', 'newest_stable');
        $update_plugins = (int) AutoUpdater_Config::get('update_plugins', 1);
        $excluded_plugins = (array) AutoUpdater_Config::get('excluded_plugins');
        $update_themes = (int) AutoUpdater_Config::get('update_themes', 0);
        $excluded_themes = (array) AutoUpdater_Config::get('excluded_themes');
        $autoupdate_at = (int) AutoUpdater_Config::get('autoupdate_at', 12);
        $sitemap_url = (string) AutoUpdater_Config::get('sitemap_url');
        $maintenance_mode = (int) AutoUpdater_Config::get('maintenance_mode', 1);
        $auto_rollback = (int) AutoUpdater_Config::get('auto_rollback', 1);
        $notification_emails = (string) AutoUpdater_Config::get('notification_emails');
        $notification_on_success = (int) AutoUpdater_Config::get('notification_on_success', 1);
        $notification_on_failure = (int) AutoUpdater_Config::get('notification_on_failure', 1);
        $vrt_css_exclusions = (string) AutoUpdater_Config::get('vrt_css_exclusions', '');

        $plugins_list = array();
        $plugins_list_unprepared = get_plugins();
        foreach ($plugins_list_unprepared as $slug => $extension) {
            if ($slug !== AUTOUPDATER_WP_PLUGIN_SLUG) {
                $plugins_list[$slug] = array(
                    'name' => $extension['Name'],
                    'excluded' => in_array($slug, $excluded_plugins),
                );
            }
        }
        unset($plugins_list_unprepared);
        $plugins_list_count = count($plugins_list);

        $themes_list = array();
        $themes_list_unprepared = version_compare(AUTOUPDATER_WP_VERSION, '3.4.0', '>=')
            ? wp_get_themes() : get_allowed_themes();
        foreach ($themes_list_unprepared as $slug => $theme) {
            $legacy = !($theme instanceof WP_Theme);
            $slug = $legacy ? $theme['Template'] : pathinfo($slug, PATHINFO_FILENAME);
            $themes_list[$slug] = array(
                'name' => $legacy ? $theme['Name'] : $theme->get('Name'),
                'excluded' => in_array($slug, $excluded_themes),
            );
        }
        unset($themes_list_unprepared);
        $themes_list_count = count($themes_list);

        $current_offset = get_option('gmt_offset');
        $offset = $time_zone = get_option('timezone_string');

        if (strpos($time_zone, 'Etc/GMT') !== false) {
            $time_zone = null;
        }

        $date = date_create(null, new DateTimeZone('UTC'));
        $date->setTime(0, 0, 0);

        if (!$time_zone) {
            $offset = $time_zone = 'UTC';
            $modifier = $current_offset < 0 ? '-' : '+';
            $date->modify($modifier . abs($current_offset) . ' hours');
            $offset .= $modifier . abs($current_offset);
        }

        $date->setTimezone(new DateTimeZone($time_zone));

        $autoupdate_at_options = array(
            0 => $date->format('H:i - ')
                . $date->modify('+6 hours')->format('H:i ') . $offset,
            6 => $date->format('H:i - ')
                . $date->modify('+6 hours')->format('H:i ') . $offset,
            12 => $date->format('H:i - ')
                . $date->modify('+6 hours')->format('H:i ') . $offset,
            18 => $date->format('H:i - ')
                . $date->modify('+6 hours')->format('H:i ') . $offset,
        );

        $template_enabled = AutoUpdater_Config::get('page_enabled_template');
        $template_disabled = AutoUpdater_Config::get('page_disabled_template');

        $white_labelling = AutoUpdater_WP_Whitelabelling::getInstance();
        $plugin_name = $white_labelling->getWhiteLabeledName(AUTOUPDATER_WP_PLUGIN_NAME);
        $author = $white_labelling->getWhiteLabeledAuthor(AUTOUPDATER_WP_PLUGIN_AUTHOR);

        $worker_token = AutoUpdater_Config::get('worker_token');
        $aes_key = AutoUpdater_Config::get('aes_key');
        $ssl_verify = AutoUpdater_Config::get('ssl_verify', 0);
        $encrypt_response = AutoUpdater_Config::get('encrypt_response', 1);
        $debug = AutoUpdater_Config::get('debug', 0);
        $protect = AutoUpdater_Config::get('protect_child', 1);

        require_once AUTOUPDATER_WP_PLUGIN_PATH . 'tmpl/' . '/configuration.tmpl.php';
    }

    private function checkAutoupdaterEnableButtonPresence()
    {
        $page_disabled_template = AutoUpdater_Config::get('page_disabled_template');
        if (strpos($page_disabled_template, 'autoupdater-enable') === false) {
            $page_disabled_template .= '<button type="button"  class="autoupdater-enable button button-primary">' .
                translate('Enable automatic updates', 'autoupdater') .
                '</button>';
        }

        return $page_disabled_template;
    }

    public function ajaxSaveConfiguration()
    {
        $response = AutoUpdater_Response::getInstance();
        $result = check_ajax_referer('save-configuration');
        $protect = AutoUpdater_Config::get('protect_child', 1);

        if (!$result) {
            $response->setCode(400)->send();
            return;
        }

        if (isset($_POST['ssl_verify'])) {
            $ssl_verify = (int) $_POST['ssl_verify'] ? 1 : 0;
            $result = AutoUpdater_Config::set('ssl_verify', $ssl_verify) && $result;
        }

        if (isset($_POST['encrypt_response'])) {
            $encrypt_response = (int) $_POST['encrypt_response'] ? 1 : 0;
            $result = AutoUpdater_Config::set('encrypt_response', $encrypt_response) && $result;
        }

        if (isset($_POST['debug'])) {
            $debug = (int) $_POST['debug'] ? 1 : 0;
            $result = AutoUpdater_Config::set('debug', $debug) && $result;
        }

        if (!$result) {
            $response->setCode(400)->send();
            return;
        }

        if (!empty($_POST['notification_emails'])) {
            if (strlen($_POST['notification_emails']) > 500) {
                $response->setCode(400)->setBody('error_email')->send();
                return;
            }
            $emails = explode(',', $_POST['notification_emails']);
            foreach ($emails as $email) {
                if (filter_var(trim($email), FILTER_VALIDATE_EMAIL) === false) {
                    $response->setCode(400)->setBody('error_email')->send();
                    return;
                }
            }
        }

        if (!empty($_POST['sitemap_url']) && (strlen($_POST['sitemap_url']) > 255 || filter_var(trim($_POST['sitemap_url']), FILTER_VALIDATE_URL) === false)) {
            $response->setCode(400)->setBody('error_sitemap')->send();
            return;
        }

        $settings = array(
            'autoupdater_enabled' => isset($_POST['autoupdater_enabled']) ? (int) $_POST['autoupdater_enabled'] : 1,
            'update_core' => isset($_POST['update_core']) ? (int) $_POST['update_core'] : 0,
            'update_plugins' => isset($_POST['update_plugins']) ? (int) $_POST['update_plugins'] : 1,
            'excluded_plugins' => isset($_POST['excluded_plugins']) ? $_POST['excluded_plugins'] : array(),
            'update_themes' => isset($_POST['update_themes']) ? (int) $_POST['update_themes'] : 0,
            'excluded_themes' => isset($_POST['excluded_themes']) ? $_POST['excluded_themes'] : array(),
            'autoupdate_at' => isset($_POST['autoupdate_at']) ? (int) $_POST['autoupdate_at'] : 0,
            'sitemap_url' => isset($_POST['sitemap_url']) ? $_POST['sitemap_url'] : '',
            'maintenance_mode' => isset($_POST['maintenance_mode']) ? (int) $_POST['maintenance_mode'] : 1,
            'auto_rollback' => isset($_POST['auto_rollback']) ? (int) $_POST['auto_rollback'] : 1,
            'notification_emails' => isset($_POST['notification_emails']) ? $_POST['notification_emails'] : '',
            'notification_on_success' => isset($_POST['notification_on_success']) ? (int) $_POST['notification_on_success'] : 1,
            'notification_on_failure' => isset($_POST['notification_on_failure']) ? (int) $_POST['notification_on_failure'] : 1,
            'vrt_css_exclusions' => isset($_POST['vrt_css_exclusions']) ? (string) $_POST['vrt_css_exclusions'] : '',
        );

        if (!$protect && isset($_POST['worker_token'])) {
            $settings['worker_token'] = substr(preg_replace('/[^a-z0-9]/i', '', $_POST['worker_token']), 0, 32);
        }
        if (!$protect && isset($_POST['aes_key'])) {
            $settings['aes_key'] = substr(preg_replace('/[^a-z0-9]/i', '', $_POST['aes_key']), 0, 32);
        }

        $result = AutoUpdater_Config::saveAutoUpdaterConfigByApi($settings);
        foreach ($settings as $field_name => $field_value) {
            if (!$result) {
                break;
            }

            $result = AutoUpdater_Config::set($field_name, $field_value);
        }

        if (!$result) {
            $response->setCode(400);
        }
        $response->send();
    }
}
