<?php
defined('AUTOUPDATER_LIB') or die;

class AutoUpdater_Task_PostBackuptoolSetup extends AutoUpdater_Task_Base
{
    /**
     * @return array
     */
    public function doTask()
    {
        $options = array(
            'htaccess_disable' => (bool) $this->input('htaccess_disable', false),
            'backup_part_size' => (int) $this->input('backup_part_size', 0),
        );

        return AutoUpdater_Backuptool::getInstance()
            ->setup(null, null, null, null, 0, $options);
    }
}
