<?php
defined('AUTOUPDATER_LIB') or die;

class AutoUpdater_Task_PostExtensionEnable extends AutoUpdater_Task_Base
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
            throw new AutoUpdater_Exception_Response('No extensions to activate', 400);
        }

        // TODO check if extensions exist

        require_once ABSPATH . 'wp-admin/includes/plugin.php';
        $result = activate_plugins($plugins);
        if (!is_wp_error($result)) {
            return array(
                'success' => true,
            );
        }

        /** @var WP_Error $result */
        $data = array(
            'success' => false,
            'error' => array(
                'code' => $result->get_error_code(),
                'message' => $result->get_error_message(),
            ),
        );

        if (count($plugins) > 1 && count($messages = $result->get_error_messages()) > 1) {
            $data['error']['messages'] = $messages;
        }

        return $data;
    }
}
