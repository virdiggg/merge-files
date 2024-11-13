<?php

namespace Virdiggg\MergeFiles\Helpers;

class StrHelper
{
    /**
     * Emoticons.
     *
     * @param array $OwO
     */
    public $OwO;

    public function __construct()
    {
        $this->OwO = [
            '╰(*°▽°*)╯', '(❁´◡`❁)', "(*/ω＼*)", '(^///^)', '☆*: .｡. o(≧▽≦)o .｡.:*☆', "(●'◡'●)", 'ヾ(≧▽≦*)o',
            'ψ(｀∇´)ψ', 'φ(*￣0￣)', '（￣︶￣）↗', 'q(≧▽≦q)', '*^____^*', '(～￣▽￣)～', '( •̀ ω •́ )✧', '[]~(￣▽￣)~*',
            'O(∩_∩)O', 'o(*^＠^*)o', 'φ(゜▽゜*)♪', '(*^▽^*)', "`(*>﹏<*)′", '(✿◡‿◡)', '(●ˇ∀ˇ●)', '(´▽`ʃ♡ƪ)', '(≧∇≦)ﾉ',
            '(*^_^*)', '（*＾-＾*）', '\^o^/', '(￣y▽￣)╭ Ohohoho.....', '○( ＾皿＾)っ Hehehe…', '(‾◡◝)', '(o゜▽゜)o☆',
            '(〃￣︶￣)人(￣︶￣〃)', '(^_-)db(-_^)', 'o(*￣▽￣*)ブ', 'o(*^▽^*)┛', '(≧∀≦)ゞ', '♪(^∇^*)', 'o(*￣▽￣*)ブ',
            '(oﾟvﾟ)ノ', 'o(*￣︶￣*)o', '( $ _ $ )', '(/≧▽≦)/', 'o(*≧▽≦)ツ┏━┓', 'ㄟ(≧◇≦)ㄏ', 'ヾ(＠⌒ー⌒＠)ノ', '(☆▽☆)',
            'ヾ(≧ ▽ ≦)ゝ', 'o((>ω< ))o', '( *︾▽︾)', '(((o(*ﾟ▽ﾟ*)o)))', '＼(((￣(￣(￣▽￣)￣)￣)))／', '( *^-^)ρ(^0^* )',
            '♪(´▽｀)', "~~~///(^v^)\\\\\\\~~~", 'o(*￣▽￣*)o', '(p≧w≦q)', 'ƪ(˘⌣˘)ʃ', '( •̀ ω •́ )y'
        ];
    }

    /**
     * Get latest migration order.
     * Default is sequential, if there is no migration file exist.
     *
     * @param string $path
     *
     * @return string
     */
    public function latest($migrationType, $path)
    {
        if ($migrationType === 'timestamp') {
            return date('YmdHis');
        }

        print($this->redText('WARNING: CODEIGNITER 3 MIGRATION CANNOT HANDLE MIGRATION NUMBER 1000, PLEASE USE TO TIMESTAMP INSTEAD ╰(*°▽°*)╯'));

        // Get all migration files.
        $seeders = $path . '*.php';
        $globs = array_filter(glob($seeders), 'is_file');
        if (count($globs) > 0) {
            // Reverse the array.
            rsort($globs);

            // Get the latest array order.
            $latestMigration = (int) $this->before($this->afterLast($globs[0], '\\'), '_');
            $count = str_pad($latestMigration + 1, $this->countLatest($latestMigration), '0', STR_PAD_LEFT);
        } else {
            // Default is sequential order, not timestamp.
            $count = '001';
        }

        return $count;
    }

    /**
     * Random string.
     *
     * @param string|int $length
     *
     * @return string
     */
    public function rand($length = 4)
    {
        $pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        return substr(str_shuffle(str_repeat($pool, ceil($length / strlen($pool)))), 0, $length);
    }

    /**
     * Parse file name with .php extension.
     *
     * @param string $name
     *
     * @return string
     */
    public function parseFileName($name)
    {
        return $name . '.php';
    }

    /**
     * Determine if a given string starts with a given substring. Case sensitive.
     * Stolen from laravel helper.
     *
     * @param  string  $haystack
     * @param  string|string[]  $needles
     * @return bool
     */
    public function startsWith($haystack, $needles)
    {
        foreach ((array) $needles as $needle) {
            if ((string) $needle !== '' && strncmp($haystack, $needle, strlen($needle)) === 0) {
                return TRUE;
            }
        }

        return FALSE;
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
     * Get the portion of a string before the last occurrence of a given value.
     * Stolen from laravel helper.
     *
     * @param  string  $subject
     * @param  string  $search
     * @return string
     */
    public function beforeLast($subject, $search)
    {
        if ($search === '') {
            return $subject;
        }

        $pos = mb_strrpos($subject, $search);

        if ($pos === FALSE) {
            return $subject;
        }

        return substr($subject, 0, $pos);
    }

    /**
     * Return the remainder of a string after the last occurrence of a given value.
     * Stolen from laravel helper.
     *
     * @param  string  $subject
     * @param  string  $search
     * @return string
     */
    public function afterLast($subject, $search)
    {
        if ($search === '') {
            return $subject;
        }

        $position = strrpos($subject, (string) $search);

        if ($position === FALSE) {
            return $subject;
        }

        return substr($subject, $position + strlen($search));
    }

    /**
     * Count latest migration. Return 3 digit number by default.
     * 
     * @param int $latest
     * 
     * @return int
     */
    public function countLatest($latest) {
        // To verify if the next number digit is increased or not.
        // Ex. strlen(800 + 1) = 3
        $nextNumber = strlen($latest + 1);
        // Count the next digit.
        // Ex. strlen(800) + 1 = 4
        $nextDigit = strlen($latest) + 1;
        $result = $nextNumber === $nextDigit ? $nextDigit : $nextNumber;
        return $result < 3 ? 3 : $result;
    }

    /**
     * Parse title page.
     *
     * @param string $text
     *
     * @return string
     */
    public function parseTitle($text)
    {
        return ucwords($this->parseWhiteSpace($text));
    }

    /**
     * Parse special character to whitespace.
     *
     * @param string $text
     *
     * @return string
     */
    public function parseWhiteSpace($text)
    {
        return preg_replace("/[^a-zA-Z0-9\s]/", ' ', $text);
    }

    /**
     * Parse returned text with red color.
     *
     * @param string $text
     *
     * @return string
     */
    public function redText($text)
    {
        return "\e[31m" . $text . "\033[0m" . "\n";
    }

    /**
     * Parse returned text with green color.
     *
     * @param string $text
     * @param bool   $withEmoticon
     *
     * @return string
     */
    public function greenText($text, $withEmoticon = true)
    {
        return "\033[92m" . ($withEmoticon ? $this->emoticon($text) : $text) . "\033[0m" . "\n";
    }

    /**
     * Parse returned text with yellow color.
     *
     * @param string $text
     *
     * @return string
     */
    public function yellowText($text)
    {
        return "\033[93m" . $text . "\033[0m" . "\n";
    }

    /**
     * Parse returned text with emoticon for fun h3h3.
     *
     * @param string $text
     *
     * @return string
     */
    public function emoticon($text)
    {
        return $text . ' ' . $this->OwO[array_rand($this->OwO, 1)];
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