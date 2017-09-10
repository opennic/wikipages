<?php
/**
 * InlineTOC-Plugin: Renders the page's toc inside the page content
 *
 * @license GPL v2 (http://www.gnu.org/licenses/gpl.html)
 * @author  Andreone
 */

if (!defined('DOKU_INC')) define('DOKU_INC', realpath(dirname(__FILE__) . '/../../') . '/');
require_once(DOKU_INC . 'inc/init.php');
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN', DOKU_INC . 'lib/plugins/');
require_once(DOKU_PLUGIN . 'syntax.php');

class syntax_plugin_inlinetoc extends DokuWiki_Syntax_Plugin {

    /**
     * What kind of syntax are we?
     */
    function getType() {
        return 'substition';
    }

    /**
     * Where to sort in? (took the same as for ~~NOTOC~~)
     */
    function getSort() {
        return 30;
    }
    
    /**
     * What kind of type are we?
     */
    function getPType() {
            return 'block';
    }

    /**
     * Connect pattern to lexer
     */
    function connectTo($mode) {
        $this->Lexer->addSpecialPattern('{{INLINETOC}}', $mode, 'plugin_inlinetoc');
    }

    /**
     * Handle the match
     */
    function handle($match, $state, $pos, &$handler) {
        return '';
    }

    /**
     * Add placeholder to cached page (will be replaced by action component)
     */
    function render($mode, &$renderer, $data) {
    	
    	if ($mode == 'metadata') {
			$renderer->meta['movetoc'] = true;
			return true;
		}
    
        $renderer->doc .= '<!-- INLINETOCPLACEHOLDER -->';
    }
}
