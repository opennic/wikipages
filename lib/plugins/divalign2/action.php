<?php
/**
 * @file       divalign2/action.php
 * @brief      Action component for the divalign2 plugin.
 * 
 * See common.php for more information.
**/

if(!defined('DOKU_INC')) die();
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'action.php');
require_once(DOKU_PLUGIN. 'divalign2/common.php'); // for common functions

class action_plugin_divalign2 extends DokuWiki_Action_Plugin {


function register(&$controller) {
    // detect DokuWiki version
    $v= file_get_contents(DOKU_INC. 'VERSION');
    if ($v===false) return;
    // else
    $v= substr($v, 0, strpos($v, ' '));
    $v_is_old= intval ($v < '2010-11-07');
    //echo "<pre> Version: $v : $v_is_old</pre>";
    if ($v_is_old) {
        // temporarily dropping the stack-para support for Lemming and below
        //$res= $controller->register_hook('TPL_CONTENT_DISPLAY', 'BEFORE', $this, 'fix_par_stack', array ());
        }
    if (0) $res= $controller->register_hook('TOOLBAR_DEFINE', 'AFTER', $this, 'divalign_toolbar', array ());

    }

function fix_par_stack (&$event, $param) {
    DW_common_divalign2::FixRenderStack($event->data, 0);
    }

function divalign_toolbar(& $event, $param) {
        $icobase = '../../plugins/divalign2/images';
        $btn = array (
            'type' => 'picker',
            'title' => 'Alignment',
            //'key' => 'a',
            'icobase' => $icobase,
            'list' => array (
                '#;;\nParagraph\n#;;\n' => 'pleft.png',
                ';#;\nParagraph\n;#;\n' => 'pcenter.png',
                ';;#\nParagraph\n;;#\n' => 'pright.png',
                '###\nParagraph\n###\n' => 'pjustify.png',
            ),
            'block' => 'false',
        );
        $event->data[]= $btn;
    }

}
//... and that's all
