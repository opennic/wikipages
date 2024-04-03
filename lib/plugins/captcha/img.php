<?php
/**
 * CAPTCHA antispam plugin - Image generator
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Andreas Gohr <gohr@cosmocode.de>
 */

if (!defined('DOKU_INC')) define('DOKU_INC', dirname(__FILE__) . '/../../../');
define('NOSESSION', true);
define('DOKU_DISABLE_GZIP_OUTPUT', 1);
require_once(DOKU_INC . 'inc/init.php');
require_once(DOKU_INC . 'inc/auth.php');

$ID = $_REQUEST['id'];
/** @var helper_plugin_captcha $plugin */
$plugin = plugin_load('helper', 'captcha');
$rand = $plugin->decrypt($_REQUEST['secret']);
$ident= $plugin->_fixedIdent();
$code = $plugin->_generateCAPTCHA($ident, $rand);
$plugin->_imageCAPTCHA($code);
