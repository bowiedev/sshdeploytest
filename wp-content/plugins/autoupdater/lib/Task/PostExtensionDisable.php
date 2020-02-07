<?php
defined('AUTOUPDATER_LIB') or die;

class AutoUpdater_Task_PostExtensionDisable extends AutoUpdater_Task_Base
{
    /**
     * @return array
     */
    public function doTask()
    {
        $extensions = (array)$this->input('extensions', array());

        $plugins = array();
        foreach ($extensions as $extension) {
            if (empty($extension['slug'])) {
                continue;
            }
            $plugins[] = $extension['slug'];
        }

        // Skip AutoUpdater extension
        if (($key = array_search(AUTOUPDATER_WP_PLUGIN_SLUG, $plugins)) !== false) {
            unset($plugins[$key]);
        }

        if (empty($plugins)) {
            throw new AutoUpdater_Exception_Response('No extensions to deactivate', 400);
        }

        // TODO check if extensions exist

        require_once ABSPATH . 'wp-admin/includes/plugin.php';
        deactivate_plugins($plugins, true);

        return array(
            'success' => true,
        );
    }
}
