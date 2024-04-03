<?php

namespace dokuwiki\plugin\captcha;

/**
 * A simple mechanism to count login failures for IP addresses
 */
class IpCounter
{
    protected $ip;
    protected $store;

    /**
     * Initialize the counter
     */
    public function __construct()
    {
        $this->ip = clientIP(true);
        $this->store = getCacheName($this->ip, '.captchaip');
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
}
