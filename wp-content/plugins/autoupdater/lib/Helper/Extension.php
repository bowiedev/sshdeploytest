<?php
defined('AUTOUPDATER_LIB') or die;

class AutoUpdater_Helper_Extension
{
    public static function loadMasterSliderPro()
    {
        if (file_exists(WP_PLUGIN_DIR . '/masterslider/includes/init/define.php')
            && file_exists(WP_PLUGIN_DIR . '/masterslider/public/class-master-slider.php')
            && file_exists(WP_PLUGIN_DIR . '/masterslider/admin/class-master-slider-admin.php')) {
            include_once WP_PLUGIN_DIR . '/masterslider/includes/init/define.php';
            include_once WP_PLUGIN_DIR . '/masterslider/public/class-master-slider.php';
            include_once WP_PLUGIN_DIR . '/masterslider/admin/class-master-slider-admin.php';
        }
    }
}
