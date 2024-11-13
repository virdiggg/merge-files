<?php

namespace Virdiggg\MergeFiles\Helpers;

class EnvHelper
{
    public function __construct()
    {
    }

    /**
     * Check if PHP version is below 7.
     * 
     * @return bool
     */
    public function belowPHP5() {
        $version = explode('.', PHP_VERSION);
        if ($version[0] < 7) {
            return true;
        }

        return false;
    }
}