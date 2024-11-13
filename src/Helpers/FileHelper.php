<?php

namespace Virdiggg\MergeFiles\Helpers;

class FileHelper
{
    /**
     * File Pointer.
     *
     * @param object $filePointer
     */
    public $filePointer;

    public function __construct()
    {
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
}