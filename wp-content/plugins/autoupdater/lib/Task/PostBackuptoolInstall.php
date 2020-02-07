<?php
defined('AUTOUPDATER_LIB') or die;

class AutoUpdater_Task_PostBackuptoolInstall extends AutoUpdater_Task_Base
{
    /**
     * @throws AutoUpdater_Exception_Response
     *
     * @return array
     */
    public function doTask()
    {
        // Get credentials from the request payload
        $dir = $this->input('directory');
        $login = $this->input('login');
        $password = $this->input('password');
        $secret = $this->input('secret');
        $max_backup_id = (int) $this->input('max_backup_id');

        if (empty($dir) || empty($login) || empty($password) || empty($secret)) {
            // Get credentials from AutoUpdater API
            $response = AutoUpdater_Request::api(
                'get',
                'backuptool/credentials',
                null,
                $this->input('site_id')
            );

            if (!empty($response->body->credentials)) {
                $credentials = $response->body->credentials;
                $dir = !empty($credentials->directory) ? $credentials->directory : null;
                $login = !empty($credentials->login) ? $credentials->login : null;
                $password = !empty($credentials->password) ? $credentials->password : null;
                $secret = !empty($credentials->secret) ? $credentials->secret : null;
                $max_backup_id = !empty($credentials->max_backup_id) ? (int) $credentials->max_backup_id : 0;
            }
        }

        if (empty($dir) || empty($login) || empty($password) || empty($secret)) {
            throw new AutoUpdater_Exception_Response('Failed to get backup tool credentials', 400);
        }

        $options = array(
            'site_id' => (int) $this->input('site_id', 0),
            'download_url' => $this->input('download_url'),
            'htaccess_disable' => (bool) $this->input('htaccess_disable', false),
            'backup_part_size' => (int) $this->input('backup_part_size', 0),
        );

        return AutoUpdater_Backuptool::getInstance()
            ->install($dir, $login, $password, $secret, $max_backup_id, $options);
    }
}
