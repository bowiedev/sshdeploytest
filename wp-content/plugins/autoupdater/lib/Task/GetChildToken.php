<?php
defined('AUTOUPDATER_LIB') or die;

class AutoUpdater_Task_GetChildToken extends AutoUpdater_Task_Base
{
    /**
     * @return array
     */
    public function doTask()
    {
        if (!defined('WPE_APIKEY') || empty(WPE_APIKEY)) {
            throw AutoUpdater_Exception_Response::getException(
                404,
                'Failed to get the token',
                'wpe_apikey_missing',
                'Missing WPE_APIKEY in the install.'
            );
        }

        $data = array(
            'success' => true,
            'token' => md5('wpe_auth_salty_dog|' . WPE_APIKEY),
        );

        return $data;
    }
}
