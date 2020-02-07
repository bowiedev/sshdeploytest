<?php
defined('AUTOUPDATER_LIB') or die;

class AutoUpdater_Task_PostBackuptoolUninstall extends AutoUpdater_Task_Base
{
    /**
     * @throws AutoUpdater_Exception_Response
     *
     * @return array
     */
    public function doTask()
    {
        $result = AutoUpdater_Backuptool::getInstance()
            ->uninstall();

        return array('success' => $result !== false);
    }
}
