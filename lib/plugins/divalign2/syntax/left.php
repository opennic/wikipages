<?php
/**
 * @file       divalign2/syntax/left.php
 * @brief      Left alignment component for divalign2 plugin.
 * 
 * See common.php for more information.
 */

// must be run within DokuWiki
if(!defined('DOKU_INC')) die();
if(!defined('DW_LF')) define('DW_LF',"\n");

if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');
require_once(DOKU_PLUGIN. 'divalign2/common.php'); // for common functions

/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class syntax_plugin_divalign2_left extends syntax_plugin_divalign2_common {
    function connectTo($mode) {
        $this->Lexer->addEntryPattern('#;;(?=.+\n#;;)',
            $mode,'plugin_divalign2_left');
    }

    function postConnect() {
        $this->Lexer->addExitPattern('\n#;;',
            'plugin_divalign2_left');
    }

    function handle($match, $state, $pos, Doku_Handler $handler){
        $align= 'left';
        $content= $match;
        $match= array ('content' => $content, 'align'=>$align);
        parent::handle($match, $state, $pos, $handler);
        return array($align,$state,$pos);
    }

}
