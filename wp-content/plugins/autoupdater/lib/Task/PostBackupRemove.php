<?php
defined('AUTOUPDATER_LIB') or die;

class AutoUpdater_Task_PostBackupRemove extends AutoUpdater_Task_Base
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

        if (empty($backup)) {
            throw new AutoUpdater_Exception_Response('Backup file was not found', 404);
        }

        if (!$backup['exists']) {
            $success = true;
        } else {
            $filemanager = AutoUpdater_Filemanager::getInstance();
            $success = $filemanager->delete($backup['path']);
            $backup['exists'] = !$success;

            if ($backup['multipart'] > 1) {
                $parts = (int) $backup['multipart'] - 1;
                $path = $backup['path'];

                do {
                    $path = substr($path, 0, -2) . sprintf('%02d', $parts);
                    if ($filemanager->is_file($backup['path'])) {
                        $success = $success && $filemanager->delete($backup['path']);
                    }
                    $parts--;
                } while ($parts > 0);
            }
        }

        return array(
            'success' => $success,
            'backup' => $backup,
        );
    }
}
