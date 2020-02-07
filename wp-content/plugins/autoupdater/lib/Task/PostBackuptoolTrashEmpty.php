<?php
defined('AUTOUPDATER_LIB') or die;

class AutoUpdater_Task_PostBackuptoolTrashEmpty extends AutoUpdater_Task_Base
{
    /**
     * @return array
     */
    public function doTask()
    {
        $result = true;
        $path = AUTOUPDATER_SITE_PATH . 'autoupdater_oldfiles/';
        $filemanager = AutoUpdater_Filemanager::getInstance();

        if ($filemanager->is_dir($path)) {
            $trash_bin = basename($this->input('trash_bin'));
            if ($trash_bin) {
                // Remove the given trash bin
                if ($filemanager->is_dir($path . $trash_bin)) {
                    $result = $filemanager->rmdir($path . $trash_bin, true);
                }
            } else {
                // Remove all trash bins
                $result = $filemanager->rmdir($path, true);
            }
        }

        return array(
            'success' => $result,
        );
    }
}
