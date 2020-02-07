<?php
defined('AUTOUPDATER_LIB') or die;

class AutoUpdater_Db
{
    protected static $instance = null;

    /**
     * @return static
     */
    public static function getInstance()
    {
        if (!is_null(static::$instance)) {
            return static::$instance;
        }

        $class_name = AutoUpdater_Loader::loadClass('Db');

        static::$instance = new $class_name();

        return static::$instance;
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        global $wpdb;

        return array(
            'name' => DB_NAME,
            'user' => DB_USER,
            'password' => DB_PASSWORD,
            'host' => rtrim(DB_HOST, ':'),
            'prefix' => $wpdb->prefix,
            'driver' => class_exists('mysqli') ? 'mysqli' : 'mysql',
        );
    }

    /**
     * @param string $sql
     *
     * @return null|bool
     */
    public function doQuery($sql)
    {
        global $wpdb;

        return $wpdb->query($sql);
    }

    /**
     * @param string $sql
     *
     * @return array
     */
    public function doQueryWithResults($sql)
    {
        global $wpdb;

        return $wpdb->get_results($sql, ARRAY_A);
    }
}
