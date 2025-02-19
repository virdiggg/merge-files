<?php

namespace Virdiggg\MergeFiles\Helpers;

class StrHelper
{
    public function __construct()
    {
    }

    /**
     * Get the portion of a string before the first occurrence of a given value.
     * Stolen from laravel helper.
     *
     * @param  string  $subject
     * @param  string  $search
     * @return string
     */
    public function before($subject, $search)
    {
        if ($search === '') {
            return $subject;
        }

        $result = strstr($subject, (string) $search, TRUE);

        return $result === FALSE ? $subject : $result;
    }

    /**
     * Cleaning the text.
     * Trim whitespace, delete unicode NO-BREAK SPACE/nbsp (U+00a0).
     */
    public function clean($text)
    {
        return trim(preg_replace('/\xc2\xa0/', '', $text));
    }
}