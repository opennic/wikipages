<?php

namespace dokuwiki\plugin\captcha;

/**
 * A simple mechanism to count login failures for IP addresses
 *
 * Counter files are stored in date-based directories for easy cleanup.
 * Note: Counters reset at midnight when a new directory is used.
 */
class IpCounter
{
    /** @var string The client IP address being tracked */
    protected $ip;

    /** @var string File path where the failure counter is stored */
    protected $store;

    /** @var int Base delay in seconds for exponential timeout calculation */
    protected $base;

    /** @var int Maximum delay in seconds (cap for exponential timeout) */
    protected $max;

    /**
     * Initialize the counter
     */
    public function __construct()
    {
        global $conf;
        $this->ip = clientIP(true);
        $this->store = $conf['tmpdir'] . '/captcha/ip/' . date('Y-m-d') . '/' . md5($this->ip) . '.ip';
        io_makeFileDir($this->store);

        $this->base = (int)($conf['plugin']['captcha']['logindenial'] ?? 0);
        $this->max = (int)($conf['plugin']['captcha']['logindenial_max'] ?? 3600);
    }

    /**
     * Increases the counter by adding a byte
     *
     * @return void
     */
    public function increment()
    {
        io_saveFile($this->store, '1', true);
    }

    /**
     * Return the current counter
     *
     * @return int
     */
    public function get()
    {
        return (int)@filesize($this->store);
    }

    /**
     * Reset the counter to zero
     *
     * @return void
     */
    public function reset()
    {
        @unlink($this->store);
    }

    /**
     * Get timestamp of last failed attempt
     *
     * @return int Unix timestamp, 0 if no attempts
     */
    public function getLastAttempt()
    {
        return (int)@filemtime($this->store);
    }

    /**
     * Calculate required timeout in seconds based on failure count
     *
     * Uses exponential backoff: base * 2^(count-1), capped at max.
     * First failed attempt is okay, second requires base seconds wait.
     *
     * @return int Timeout in seconds (0 if no failures or feature disabled)
     */
    public function calculateTimeout()
    {
        if ($this->base < 1) return 0;
        $count = $this->get();
        if ($count < 1) return 0;
        $timeout = $this->base * pow(2, $count - 1); // -1 because first failure is free
        return (int)min($timeout, $this->max);
    }

    /**
     * Get remaining wait time in seconds
     *
     * @return int Seconds remaining (0 if no wait needed or feature disabled)
     */
    public function getRemainingTime()
    {
        $timeout = $this->calculateTimeout();
        if ($timeout === 0) return 0;
        $elapsed = time() - $this->getLastAttempt();
        return max(0, $timeout - $elapsed);
    }

    /**
     * Remove all outdated IP counter directories
     *
     * Deletes counter directories older than today, similar to FileCookie::clean()
     *
     * @return void
     */
    public static function clean()
    {
        global $conf;
        $path = $conf['tmpdir'] . '/captcha/ip/';
        $dirs = glob("$path/*", GLOB_ONLYDIR);
        if (!$dirs) return;

        $today = date('Y-m-d');
        foreach ($dirs as $dir) {
            if (basename($dir) === $today) continue;
            if (!preg_match('/\/captcha\/ip\//', $dir)) continue; // safety net
            io_rmdir($dir, true);
        }
    }
}
