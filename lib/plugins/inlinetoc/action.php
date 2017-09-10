<?php
/**
 * InlineTOC-Plugin: Renders the page's toc inside the page content
 *
 * @license GPL v2 (http://www.gnu.org/licenses/gpl.html)
 * @author  Andreone
 */

if(!defined('DOKU_INC')) die();
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');

require_once(DOKU_PLUGIN.'action.php');

class action_plugin_inlinetoc extends DokuWiki_Action_Plugin {

    /**
     * Register event handlers
     */
    function register(&$controller) {
        $controller->register_hook('TPL_ACT_RENDER', 'BEFORE', $this, 'handle_act_render', array());
        $controller->register_hook('RENDERER_CONTENT_POSTPROCESS', 'AFTER', $this, 'handle_renderer_content_postprocess', array());
    }
   
	/**
	 * Make sure the other toc is not printed
	 */
    function handle_act_render(&$event, $param) {
        global $ID;
        global $INFO;
        if (p_get_metadata($ID, 'movetoc')) {
            $INFO['prependTOC'] = false;
        }
    }

    /**
     * Replace our placeholder with the actual toc content
     */
    function handle_renderer_content_postprocess(&$event, $param) {
        global $TOC;
        if ($TOC) {
            $html = '<div id="inlinetoc2" class="inlinetoc2">' . html_buildlist($TOC, 'inlinetoc2', array($this, 'html_list_inlinetoc2')) . '</div>';
            $event->data[1] = str_replace('<!-- INLINETOCPLACEHOLDER -->',
                                          $html,
                                          $event->data[1]);
        }
    }


	/**
	 * Callback for html_buildlist.
	 * Builds list items with inlinetoc2 printable class instead of dokuwiki's toc class which isn't printable.
	 */
	function html_list_inlinetoc2($item){
	    if(isset($item['hid'])){
	        $link = '#'.$item['hid'];
	    }else{
	        $link = $item['link'];
	    }
	
	    return '<span class="li"><a href="'.$link.'" class="inlinetoc2">'. hsc($item['title']).'</a></span>';
	}
}