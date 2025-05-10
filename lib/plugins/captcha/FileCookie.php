<?php

namespace dokuwiki\plugin\captcha;

/**
 * Remember the issuing (and use) of CAPTCHAs by placing a file on the server
 *
 * This is used to prevent replay attacks. It is generated when the captcha form
 * is shown and checked with the captcha check. Since we can not be sure about the
 * session state (might be closed or open) we're not using it.
 *
 * We're not using the stored values for displaying the captcha image (or audio)
 * but continue to use our encryption scheme. This way it's still possible to have
 * multiple captcha checks going on in parallel (eg. with multiple browser tabs)
 */
class FileCookie
{
    protected $path;

    /**
     * Initialize the cookie
     *
     * @param $fixed string the fixed part, any string
     * @param $rand  float  some random number between 0 and 1
     */
    public function __construct($ident, $rand)
    {
        global $conf;
        $this->path = $conf['tmpdir'] . '/captcha/' . date('Y-m-d') . '/' . md5($ident . $rand) . '.cookie';
        io_makeFileDir($this->path);
    }

    /**
     * Creates a one time captcha cookie
     */
    public function set()
    {
        touch($this->path);
    }

    /**
     * Checks if the captcha cookie exists and deletes it
     *
     * @return bool true if the cookie existed
     */
    public function check()
    {
        if (file_exists($this->path)) {
            unlink($this->path);
            return true;
        }
        return false;
    }

    /**
     * remove all outdated captcha cookies
     */
    public static function clean()
    {
        global $conf;
        $path = $conf['tmpdir'] . '/captcha/';
        $dirs = glob("$path/*", GLOB_ONLYDIR);
        $today = date('Y-m-d');
        foreach ($dirs as $dir) {
            if (basename($dir) === $today) continue;
            if (!preg_match('/\/captcha\//', $dir)) continue; // safety net
            io_rmdir($dir, true);
        }
    }
}
