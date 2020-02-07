<?php
defined('AUTOUPDATER_LIB') or die;

class AutoUpdater_Task_GetBackupDownload extends AutoUpdater_Task_Base
{
    /**
     * @throws AutoUpdater_Exception_Response
     *
     * @return array
     */
    public function doTask()
    {
        $id = $this->input('id', null);
        $filename = $this->input('filename', null);

        $backup = AutoUpdater_Backuptool::getInstance()
            ->getBackup($id, $filename);

        if (empty($backup['exists'])) {
            throw new AutoUpdater_Exception_Response('Backup file was not found', 404);
        }

        AutoUpdater_Response::getInstance()
            ->sendFile($backup['path']);
    }
}
