<?php

namespace Virdiggg\MergeFiles\Helpers;

class FileHelper
{
    public function __construct()
    {
    }

    /**
     * Check if file exists and is readable.
     * 
     * @param string $filename
     * @return boolean
     */
    public function fileExists($filename)
    {
        // Not readable
        if (is_readable($filename) === false) {
            return false;
        }

        // Not a file or directory
        if (is_file($filename) === false && is_dir($filename) === false) {
            return false;
        }

        // File or directory is not exist
        if (file_exists($filename) === false) {
            return false;
        }
        // if (file_exists($filename) === false || is_readable($filename) === false) {
        //     return false;
        // }

        try {
            $filehandle = fopen($filename, 'r');
        } catch (\Exception $e) {
            return false;
        }

        // File corrupted
        if ($filehandle === false) {
            return false;
        }

        fclose($filehandle);
        return true;
    }

    /**
     * Create folder with 0755 (rwxr-xr-x) permission if doesn't exist.
     * If exists, change its permission to 0755 (rwxrwxrwx).
     * Owner default to www-data:www-data.
     *
     * @param string $path
     * @param string $mode
     * @param string $owner
     *
     * @return void
     */
    public function folderPermission($path, $mode = 0755, $owner = 'www-data:www-data')
    {
        if (!is_dir($path)) {
            // If folder doesn't exist, create a new one with permission (rwxrwxrwx).
            $old = umask(0);
            mkdir($path, $mode, TRUE);
            @chown($path, $owner);
            // @chgrp($path, $owner);
            umask($old);
        } else {
            // If exists, change its permission to 0755 (rwxr-xr-x).
            $old = umask(0);
            @chmod($path, $mode);
            @chown($path, $owner);
            // @chgrp($path, $owner);
            umask($old);
        }
    }

    /**
     * Open file with mode.
     *
     * @param string $file
     *
     * @return void
     */
    public function removeFile($file)
    {
        @unlink($file);
    }
}