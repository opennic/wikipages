<?php

/**
 * CAPTCHA antispam plugin - sound generator
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Andreas Gohr <gohr@cosmocode.de>
 */

if (!defined('DOKU_INC')) define('DOKU_INC', __DIR__ . '/../../../');
define('NOSESSION', true);
define('DOKU_DISABLE_GZIP_OUTPUT', 1);
require_once(DOKU_INC . 'inc/init.php');
require_once(DOKU_INC . 'inc/auth.php');

global $INPUT;
global $ID;

$ID = $INPUT->str('id');

/** @var helper_plugin_captcha $plugin */
$plugin = plugin_load('helper', 'captcha');

if ($plugin->getConf('mode') != 'audio' && $plugin->getConf('mode') != 'svgaudio') {
    http_status(404);
    exit;
}

header('Content-type: audio/x-wav');
header('Content-Disposition: attachment;filename=captcha.wav');

$code = $plugin->generateCaptchaCode(
    $plugin->fixedIdent(),
    $plugin->decrypt($INPUT->str('secret'))
);
echo $plugin->audioCaptcha($code);
