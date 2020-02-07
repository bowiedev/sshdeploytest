<?php
defined('AUTOUPDATER_LIB') or die;

class AutoUpdater_Filemanager
{
    protected static $instance = null;

    protected $FS_CHMOD_FILE;
    protected $FS_CHMOD_DIR;
    static $wp_filesystem;
    
    public function __construct()
    {
        $this->FS_CHMOD_DIR = (fileperms(AUTOUPDATER_SITE_PATH) & 0777 | 0755);

        if (file_exists(AUTOUPDATER_SITE_PATH . 'index.php')) {
            $this->FS_CHMOD_FILE = (fileperms(AUTOUPDATER_SITE_PATH . 'index.php') & 0777 | 0644);
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        if (function_exists('WP_Filesystem') and WP_Filesystem()) {
            global $wp_filesystem;

            static::$wp_filesystem = $wp_filesystem;
        }
    }

    /**
     * @return static
     */
    public static function getInstance()
    {
        if (!is_null(static::$instance)) {
            return static::$instance;
        }

        $class_name = AutoUpdater_Loader::loadClass('Filemanager');

        static::$instance = new $class_name();

        return static::$instance;
    }

    /**
     * Reads entire file into a string
     *
     * @access public
     *
     * @param string $file Name of the file to read.
     *
     * @return string|bool The function returns the read data or false on failure.
     */
    public function get_contents($file)
    {
        if (static::$wp_filesystem) {
            return static::$wp_filesystem->get_contents($file);
        }

        return @file_get_contents($file);
    }

    /**
     * Reads entire file into an array
     *
     * @access public
     *
     * @param string $file Path to the file.
     *
     * @return array|bool the file contents in an array or false on failure.
     */
    public function get_contents_array($file)
    {
        if (static::$wp_filesystem) {
            return static::$wp_filesystem->get_contents_array($file);
        }

        return @file($file);
    }

    /**
     * Write a string to a file
     *
     * @access public
     *
     * @param string $file     Remote path to the file where to write the data.
     * @param string $contents The data to write.
     *
     * @return bool False upon failure, true otherwise.
     */
    public function put_contents($file, $contents)
    {
        return @file_put_contents($file, $contents) !== false;
    }

    /**
     * Gets the current working directory
     *
     * @access public
     *
     * @return string|bool the current working directory on success, or false on failure.
     */
    public function cwd()
    {
        if (static::$wp_filesystem) {
            return static::$wp_filesystem->cwd();
        }

        return @getcwd();
    }

    /**
     * Change directory
     *
     * @access public
     *
     * @param string $dir The new current directory.
     *
     * @return bool Returns true on success or false on failure.
     */
    public function chdir($dir)
    {
        if (static::$wp_filesystem) {
            return static::$wp_filesystem->chdir($dir);
        }

        return @chdir($dir);
    }

    /**
     * Changes file group
     *
     * @access public
     *
     * @param string $file      Path to the file.
     * @param mixed  $group     A group name or number.
     * @param bool   $recursive Optional. If set True changes file group recursively. Default false.
     *
     * @return bool Returns true on success or false on failure.
     */
    public function chgrp($file, $group, $recursive = false)
    {
        if (static::$wp_filesystem) {
            return static::$wp_filesystem->chgrp($file, $group, $recursive);
        }

        if (!$this->exists($file)) {
            return false;
        }

        if (!$recursive) {
            return @chgrp($file, $group);
        }

        if (!$this->is_dir($file)) {
            return @chgrp($file, $group);
        }

        // Is a directory, and we want recursive
        $file = $this->trailingslashit($file);
        $filelist = $this->dirlist($file);
        foreach ($filelist as $filename) {
            $this->chgrp($file . $filename, $group, $recursive);
        }

        return true;
    }

    /**
     * Changes filesystem permissions
     *
     * @access public
     *
     * @param string $file      Path to the file.
     * @param int    $mode      Optional. The permissions as octal number, usually 0644 for files,
     *                          0755 for dirs. Default false.
     * @param bool   $recursive Optional. If set True changes file group recursively. Default false.
     *
     * @return bool Returns true on success or false on failure.
     */
    public function chmod($file, $mode = false, $recursive = false)
    {
        if (static::$wp_filesystem) {
            return static::$wp_filesystem->chmod($file, $mode, $recursive);
        }

        if (!$mode) {
            if ($this->is_file($file)) {
                $mode = $this->FS_CHMOD_FILE;
            } elseif ($this->is_dir($file)) {
                $mode = $this->FS_CHMOD_DIR;
            } else {
                return false;
            }
        }

        if (!$recursive || !$this->is_dir($file)) {
            return @chmod($file, $mode);
        }

        // Is a directory, and we want recursive
        $file = $this->trailingslashit($file);
        $filelist = $this->dirlist($file);
        foreach ((array)$filelist as $filename => $filemeta) {
            $this->chmod($file . $filename, $mode, $recursive);
        }

        return true;
    }

    /**
     * Changes file owner
     *
     * @access public
     *
     * @param string $file      Path to the file.
     * @param mixed  $owner     A user name or number.
     * @param bool   $recursive Optional. If set True changes file owner recursively.
     *                          Default false.
     *
     * @return bool Returns true on success or false on failure.
     */
    public function chown($file, $owner, $recursive = false)
    {
        if (static::$wp_filesystem) {
            return static::$wp_filesystem->chown($file, $owner, $recursive);
        }

        if (!$this->exists($file)) {
            return false;
        }

        if (!$recursive) {
            return @chown($file, $owner);
        }

        if (!$this->is_dir($file)) {
            return @chown($file, $owner);
        }

        // Is a directory, and we want recursive
        $filelist = $this->dirlist($file);
        foreach ($filelist as $filename) {
            $this->chown($file . '/' . $filename, $owner, $recursive);
        }

        return true;
    }

    /**
     * Gets file owner
     *
     * @access public
     *
     * @param string $file Path to the file.
     *
     * @return string|bool Username of the user or false on error.
     */
    public function owner($file)
    {
        if (static::$wp_filesystem) {
            return static::$wp_filesystem->owner($file);
        }

        $owneruid = @fileowner($file);
        if (!$owneruid) {
            return false;
        }

        if (!function_exists('posix_getpwuid')) {
            return $owneruid;
        }

        $ownerarray = posix_getpwuid($owneruid);

        return $ownerarray['name'];
    }

    /**
     * Gets file permissions
     *
     * FIXME does not handle errors in fileperms()
     *
     * @access public
     *
     * @param string $file Path to the file.
     *
     * @return string Mode of the file (last 3 digits).
     */
    public function getchmod($file)
    {
        if (static::$wp_filesystem) {
            return static::$wp_filesystem->getchmod($file);
        }

        return substr(decoct(@fileperms($file)), -3);
    }

    /**
     * @access public
     *
     * @param string $file
     *
     * @return string|false
     */
    public function group($file)
    {
        if (static::$wp_filesystem) {
            return static::$wp_filesystem->group($file);
        }

        $gid = @filegroup($file);
        if (!$gid) {
            return false;
        }

        if (!function_exists('posix_getgrgid')) {
            return $gid;
        }

        $grouparray = posix_getgrgid($gid);

        return $grouparray['name'];
    }

    /**
     * @access public
     *
     * @param string $source
     * @param string $destination
     * @param bool   $overwrite
     * @param int    $mode
     *
     * @return bool
     */
    public function copy($source, $destination, $overwrite = false, $mode = false)
    {
        if (static::$wp_filesystem) {
            return static::$wp_filesystem->copy($source, $destination, $overwrite, $mode);
        }

        if (!$overwrite && $this->exists($destination)) {
            return false;
        }

        $rtval = copy($source, $destination);
        if ($mode) {
            $this->chmod($destination, $mode);
        }

        return $rtval;
    }

    /**
     * @access public
     *
     * @param string $source
     * @param string $destination
     * @param bool   $overwrite
     *
     * @return bool
     */
    public function move($source, $destination, $overwrite = false)
    {
        if (static::$wp_filesystem) {
            return static::$wp_filesystem->move($source, $destination, $overwrite);
        }

        if (!$overwrite && $this->exists($destination)) {
            return false;
        }

        // Try using rename first. if that fails (for example, source is read only) try copy.
        if (@rename($source, $destination)) {
            return true;
        }

        if ($this->copy($source, $destination, $overwrite) && $this->exists($destination)) {
            $this->delete($source);

            return true;
        } else {
            return false;
        }
    }

    /**
     * @access public
     *
     * @param string $file
     * @param bool   $recursive
     * @param string $type
     *
     * @return bool
     */
    public function delete($file, $recursive = false, $type = false)
    {
        if (static::$wp_filesystem) {
            return static::$wp_filesystem->delete($file, $recursive, $type);
        }

        if (empty($file)) // Some filesystems report this as /, which can cause non-expected recursive deletion of all files in the filesystem.
        {
            return false;
        }

        $file = str_replace('\\', '/', $file); // for win32, occasional problems deleting files otherwise

        if ('f' == $type || $this->is_file($file)) {
            return @unlink($file);
        }

        if (!$recursive && $this->is_dir($file)) {
            return @rmdir($file);
        }

        // At this point it's a folder, and we're in recursive mode
        $file = $this->trailingslashit($file);
        $filelist = $this->dirlist($file, true);

        $retval = true;
        if (is_array($filelist)) {
            foreach ($filelist as $filename => $fileinfo) {
                if (!$this->delete($file . $filename, $recursive, $fileinfo['type'])) {
                    $retval = false;
                }
            }
        }

        if (file_exists($file) && !@rmdir($file)) {
            $retval = false;
        }

        return $retval;
    }

    /**
     * @access public
     *
     * @param string $file
     *
     * @return bool
     */
    public function exists($file)
    {
        if (static::$wp_filesystem) {
            return static::$wp_filesystem->exists($file);
        }

        return @file_exists($file);
    }

    /**
     * @access public
     *
     * @param string $file
     *
     * @return bool
     */
    public function is_file($file)
    {
        if (static::$wp_filesystem) {
            return static::$wp_filesystem->is_file($file);
        }

        return @is_file($file);
    }

    /**
     * @access public
     *
     * @param string $path
     *
     * @return bool
     */
    public function is_dir($path)
    {
        if (static::$wp_filesystem) {
            return static::$wp_filesystem->is_dir($path);
        }

        return @is_dir($path);
    }

    /**
     * @access public
     *
     * @param string $file
     *
     * @return bool
     */
    public function is_readable($file)
    {
        if (static::$wp_filesystem) {
            return static::$wp_filesystem->is_readable($file);
        }

        return @is_readable($file);
    }

    /**
     * @access public
     *
     * @param string $file
     *
     * @return bool
     */
    public function is_writable($file)
    {
        if (static::$wp_filesystem) {
            return static::$wp_filesystem->is_writable($file);
        }

        return @is_writable($file);
    }

    /**
     * @access public
     *
     * @param string $file
     *
     * @return int
     */
    public function atime($file)
    {
        if (static::$wp_filesystem) {
            return static::$wp_filesystem->atime($file);
        }

        return @fileatime($file);
    }

    /**
     * @access public
     *
     * @param string $file
     *
     * @return int
     */
    public function mtime($file)
    {
        if (static::$wp_filesystem) {
            return static::$wp_filesystem->mtime($file);
        }

        return @filemtime($file);
    }

    /**
     * @access public
     *
     * @param string $file
     *
     * @return int
     */
    public function size($file)
    {
        if (static::$wp_filesystem) {
            return static::$wp_filesystem->size($file);
        }

        return @filesize($file);
    }

    /**
     * @access public
     *
     * @param string $file
     * @param int    $time
     * @param int    $atime
     *
     * @return bool
     */
    public function touch($file, $time = 0, $atime = 0)
    {
        if (static::$wp_filesystem) {
            return static::$wp_filesystem->touch($file, $time, $atime);
        }

        if ($time == 0) {
            $time = time();
        }

        if ($atime == 0) {
            $atime = time();
        }

        return @touch($file, $time, $atime);
    }

    /**
     * @access public
     *
     * @param string $path
     * @param mixed  $chmod
     * @param mixed  $chown
     * @param mixed  $chgrp
     *
     * @return bool
     */
    public function mkdir($path, $chmod = false, $chown = false, $chgrp = false)
    {
        if (static::$wp_filesystem) {
            return static::$wp_filesystem->mkdir($path, $chmod, $chown, $chgrp);
        }

        // Safe mode fails with a trailing slash under certain PHP versions.
        $path = $this->untrailingslashit($path);
        if (empty($path)) {
            return false;
        }

        if (!$chmod) {
            $chmod = $this->FS_CHMOD_DIR;
        }

        if (!@mkdir($path)) {
            return false;
        }

        $this->chmod($path, $chmod);
        if ($chown) {
            $this->chown($path, $chown);
        }

        if ($chgrp) {
            $this->chgrp($path, $chgrp);
        }

        return true;
    }

    /**
     * @access public
     *
     * @param string $path
     * @param bool   $recursive
     *
     * @return bool
     */
    public function rmdir($path, $recursive = false)
    {
        return $this->delete($path, $recursive);
    }

    /**
     * @param string      $url
     * @param string|null $destination Destination file name
     *
     * @return bool|string Path on success, FALSE on failure
     * @throws Exception
     */
    public function download($url, $destination = null)
    {
        $result = download_url($url);
        if (is_wp_error($result)) {
            /** @var WP_Error $result */
            $e = new AutoUpdater_Exception_Response('Failed to download file from URL: ' . $url, 200);
            $e->setError($result->get_error_code(), $result->get_error_message());
            throw $e;
        }

        if ($destination) {
            $this->move($result, $destination);

            return $destination;
        }

        return $result;
    }

    /**
     * @param string      $file
     * @param string|null $destination
     *
     * @return string Path on success
     * @throws Exception
     */
    public function unpack($file, $destination = null)
    {
        // WordPress will create destination directory if it does not exist
        if ($destination) {
            $path = $destination;
        } else {
            $path = dirname($file) . '/' . $this->getRandomName() . '/';
        }

        $result = unzip_file($file, $path);

        if (is_wp_error($result)) {
            /** @var WP_Error $result */
            $e = new AutoUpdater_Exception_Response('Failed to unpack file: ' . basename($file), 200);
            $e->setError($result->get_error_code(), $result->get_error_message());
            throw $e;
        }

        return $path;
    }

    /**
     * @return bool
     */
    public function setTempDirWitihnSite()
    {
        if (defined('WP_TEMP_DIR') && strpos(realpath(WP_TEMP_DIR), realpath(AUTOUPDATER_ROOT_PATH)) === false) {
            AutoUpdater_Log::error(sprintf('WP_TEMP_DIR is defined and it points to a location outside of the site: %s', WP_TEMP_DIR));
            return false;
        }

        if (!defined('WP_TEMP_DIR')) {
            define('WP_TEMP_DIR', WP_CONTENT_DIR . '/upgrade');
        }

        if (!$this->exists(WP_TEMP_DIR) && !$this->mkdir(WP_TEMP_DIR, 0700)) {
            AutoUpdater_Log::error(sprintf('Failed to create the directory: %s', WP_TEMP_DIR));
            return false;
        }

        if (!$this->is_writable(WP_TEMP_DIR) && !$this->chmod(WP_TEMP_DIR, 0700)) {
            AutoUpdater_Log::error(sprintf('Failed to set write permission to the directory: %s', WP_TEMP_DIR));
            return false;
        }

        return true;
    }

    /**
     * @return string
     */
    public function getTempPath()
    {
        return get_temp_dir();
    }

    /**
     * @return string
     */
    public function getRandomName()
    {
        if (function_exists('openssl_random_pseudo_bytes')) {
            return bin2hex(openssl_random_pseudo_bytes(8)); //2 characters per byte = 16 chars
        } else {
            //Eh, we gotta make do somehow
            return substr(str_shuffle(MD5(microtime())), 0, 16);
        }
    }

    public function clearPhpCache()
    {
        // Make sure that PHP has the latest data of the files.
        @clearstatcache();

        // Remove all compiled files from opcode cache.
        if (function_exists('opcache_reset')) {
            // Always reset the OPcache if it's enabled. Otherwise there's a good chance the server will not know we are
            // replacing .php scripts. This is a major concern since PHP 5.5 included and enabled OPcache by default.
            @opcache_reset();
        } elseif (function_exists('apc_clear_cache')) {
            @apc_clear_cache();
        }
    }

    /**
     * @access public
     *
     * @param string $path
     * @param bool   $include_hidden
     * @param bool   $recursive
     *
     * @return bool|array
     */
    public function dirlist($path, $include_hidden = true, $recursive = false)
    {
        if (static::$wp_filesystem) {
            return static::$wp_filesystem->dirlist($path, $include_hidden, $recursive);
        }

        if ($this->is_file($path)) {
            $limit_file = basename($path);
            $path = dirname($path);
        } else {
            $limit_file = false;
        }

        if (!$this->is_dir($path)) {
            return false;
        }

        $dir = @dir($path);
        if (!$dir) {
            return false;
        }

        $ret = array();

        while (false !== ($entry = $dir->read())) {
            $struc = array();
            $struc['name'] = $entry;

            if ('.' == $struc['name'] || '..' == $struc['name']) {
                continue;
            }

            if (!$include_hidden && '.' == $struc['name'][0]) {
                continue;
            }

            if ($limit_file && $struc['name'] != $limit_file) {
                continue;
            }

            $struc['perms'] = $this->gethchmod($path . '/' . $entry);
            $struc['permsn'] = $this->getnumchmodfromh($struc['perms']); //TODO
            $struc['number'] = false;
            $struc['owner'] = $this->owner($path . '/' . $entry);
            $struc['group'] = $this->group($path . '/' . $entry);
            $struc['size'] = $this->size($path . '/' . $entry);
            $struc['lastmodunix'] = $this->mtime($path . '/' . $entry);
            $struc['lastmod'] = date('M j', $struc['lastmodunix']);
            $struc['time'] = date('h:i:s', $struc['lastmodunix']);
            $struc['type'] = $this->is_dir($path . '/' . $entry) ? 'd' : 'f';

            if ('d' == $struc['type']) {
                if ($recursive) {
                    $struc['files'] = $this->dirlist($path . '/' . $struc['name'], $include_hidden, $recursive);
                } else {
                    $struc['files'] = array();
                }
            }

            $ret[$struc['name']] = $struc;
        }
        $dir->close();
        unset($dir);

        return $ret;
    }

    /**
     * Return the *nix-style file permissions for a file.
     *
     * From the PHP documentation page for fileperms().
     *
     * @link https://secure.php.net/manual/en/function.fileperms.php
     *
     * @param string $file String filename.
     *
     * @return string The *nix-style representation of permissions.
     */
    public function gethchmod($file)
    {
        if (static::$wp_filesystem) {
            return static::$wp_filesystem->gethchmod($file);
        }

        $perms = intval($this->getchmod($file), 8);
        if (($perms & 0xC000) == 0xC000) // Socket
        {
            $info = 's';
        } elseif (($perms & 0xA000) == 0xA000) // Symbolic Link
        {
            $info = 'l';
        } elseif (($perms & 0x8000) == 0x8000) // Regular
        {
            $info = '-';
        } elseif (($perms & 0x6000) == 0x6000) // Block special
        {
            $info = 'b';
        } elseif (($perms & 0x4000) == 0x4000) // Directory
        {
            $info = 'd';
        } elseif (($perms & 0x2000) == 0x2000) // Character special
        {
            $info = 'c';
        } elseif (($perms & 0x1000) == 0x1000) // FIFO pipe
        {
            $info = 'p';
        } else // Unknown
        {
            $info = 'u';
        }

        // Owner
        $info .= (($perms & 0x0100) ? 'r' : '-');
        $info .= (($perms & 0x0080) ? 'w' : '-');
        $info .= (($perms & 0x0040) ? (($perms & 0x0800) ? 's' : 'x') : (($perms & 0x0800) ? 'S' : '-'));

        // Group
        $info .= (($perms & 0x0020) ? 'r' : '-');
        $info .= (($perms & 0x0010) ? 'w' : '-');
        $info .= (($perms & 0x0008) ? (($perms & 0x0400) ? 's' : 'x') : (($perms & 0x0400) ? 'S' : '-'));

        // World
        $info .= (($perms & 0x0004) ? 'r' : '-');
        $info .= (($perms & 0x0002) ? 'w' : '-');
        $info .= (($perms & 0x0001) ? (($perms & 0x0200) ? 't' : 'x') : (($perms & 0x0200) ? 'T' : '-'));

        return $info;
    }

    /**
     * Convert *nix-style file permissions to a octal number.
     *
     * Converts '-rw-r--r--' to 0644
     * From "info at rvgate dot nl"'s comment on the PHP documentation for chmod()
     *
     * @link https://secure.php.net/manual/en/function.chmod.php#49614
     *
     * @param string $mode string The *nix-style file permission.
     *
     * @return int octal representation
     */
    public function getnumchmodfromh($mode)
    {
        if (static::$wp_filesystem) {
            return static::$wp_filesystem->getnumchmodfromh($mode);
        }

        $realmode = '';
        $legal = array('', 'w', 'r', 'x', '-');
        $attarray = preg_split('//', $mode);

        for ($i = 0, $c = count($attarray); $i < $c; $i++) {
            if ($key = array_search($attarray[$i], $legal)) {
                $realmode .= $legal[$key];
            }
        }

        $mode = str_pad($realmode, 10, '-', STR_PAD_LEFT);
        $trans = array('-' => '0', 'r' => '4', 'w' => '2', 'x' => '1');
        $mode = strtr($mode, $trans);

        $newmode = $mode[0];
        $newmode .= $mode[1] + $mode[2] + $mode[3];
        $newmode .= $mode[4] + $mode[5] + $mode[6];
        $newmode .= $mode[7] + $mode[8] + $mode[9];

        return $newmode;
    }

    /**
     * Appends a trailing slash.
     *
     * Will remove trailing forward and backslashes if it exists already before adding
     * a trailing forward slash. This prevents double slashing a string or path.
     *
     * @param string $string What to add the trailing slash to.
     *
     * @return string String with trailing slash added.
     */
    public function trailingslashit($string)
    {
        return $this->untrailingslashit($string) . '/';
    }

    /**
     * Removes trailing forward slashes and backslashes if they exist.
     *
     * @param string $string What to remove the trailing slashes from.
     *
     * @return string String without the trailing slashes.
     */
    public function untrailingslashit($string)
    {
        return rtrim($string, '/\\');
    }

    /**
     * Trims the path to site's root from a given path (i.e. replaces the left-most occurrence of it).
     *
     * @param string $path
     *
     * @return string
     */
    public function trimPath($path)
    {
        // Only replace the site's path from the beginning of the path to the file list
        // this makes sure that if the site's chrooted (i.e. AUTOUPDATER_SITE_PATH === '/')
        // we'll not replace every '/' in the string
        if (substr($path, 0, strlen(AUTOUPDATER_SITE_PATH)) === AUTOUPDATER_SITE_PATH) {
            $path = substr($path, strlen(AUTOUPDATER_SITE_PATH));
        }

        return $path;
    }

    /**
     * Set the mbstring internal encoding to a binary safe encoding when func_overload
     * is enabled.
     *
     * When mbstring.func_overload is in use for multi-byte encodings, the results from
     * strlen() and similar functions respect the utf8 characters, causing binary data
     * to return incorrect lengths.
     *
     * This function overrides the mbstring encoding to a binary-safe encoding, and
     * resets it to the users expected encoding afterwards through the
     * `reset_mbstring_encoding` function.
     *
     * It is safe to recursively call this function, however each
     * `mbstring_binary_safe_encoding()` call must be followed up with an equal number
     * of `reset_mbstring_encoding()` calls.
     *
     * @staticvar array $encodings
     * @staticvar bool  $overloaded
     *
     * @param bool $reset Optional. Whether to reset the encoding back to a previously-set encoding.
     *                    Default false.
     */
    protected function mbstring_binary_safe_encoding($reset = false)
    {
        static $encodings = array();
        static $overloaded = null;

        if (is_null($overloaded)) {
            $overloaded = function_exists('mb_internal_encoding') && (ini_get('mbstring.func_overload') & 2);
        }

        if (false === $overloaded) {
            return;
        }

        if (!$reset) {
            $encoding = mb_internal_encoding();
            array_push($encodings, $encoding);
            mb_internal_encoding('ISO-8859-1');
        }

        if ($reset && $encodings) {
            $encoding = array_pop($encodings);
            mb_internal_encoding($encoding);
        }
    }

    /**
     * Reset the mbstring internal encoding to a users previously set encoding.
     *
     * @see   mbstring_binary_safe_encoding()
     */
    protected function reset_mbstring_encoding()
    {
        $this->mbstring_binary_safe_encoding(true);
    }
}
