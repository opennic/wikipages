<?php
/**
 * @file       divalign2/action.php
 * @brief      Action component for the divalign2 plugin.
 * 
 * See common.php for more information.
**/

if(!defined('DOKU_INC')) die();
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
//require_once(DOKU_PLUGIN.'action.php');
require_once(DOKU_PLUGIN. 'divalign2/common.php'); // for common functions

class action_plugin_divalign2 extends DokuWiki_Action_Plugin {

function register (Doku_Event_Handler $controller) {
}

}
//... and that's all
