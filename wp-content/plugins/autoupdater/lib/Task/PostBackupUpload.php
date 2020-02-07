<?php
defined('AUTOUPDATER_LIB') or die;

class AutoUpdater_Task_PostBackupUpload extends AutoUpdater_Task_Base
{
    /**
     * @throws AutoUpdater_Exception_Response
     *
     * @return array
     */
    public function doTask()
    {
        $backup_url = $this->input('backup_url');
        $backup_filename = $this->input('backup_filename');

        if (empty($backup_url) || empty($backup_filename)) {
            throw new AutoUpdater_Exception_Response('No backup to upload', 400);
        }

        @set_time_limit(0);
        @ini_set('memory_limit', '2000M');

        $filemanager = AutoUpdater_Filemanager::getInstance();
        $backuptool = AutoUpdater_Backuptool::getInstance();

        //Build the local path
        $path = $backuptool->getPath() . 'backups/';
        if (!$filemanager->is_dir($path)) {
            return array(
                'success' => false,
                'message' => 'Backups directory does not exist',
            );
        }

        $buffer = $filemanager->get_contents($backup_url);
        if ($buffer === false) {
            return array(
                'success' => false,
                'message' => 'Failed to get remote backup',
            );
        }

        if ($filemanager->put_contents($path . $backup_filename, $buffer) === false) {
            return array(
                'success' => false,
                'message' => 'Failed to save downloaded backup',
            );
        }

        return array(
            'success' => true,
        );
    }
}
