<?php
/**
 * @file       divalign2/common.php
 * @brief      Common functions for the divalign2 plugin.
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @version    5.0rc1
 * @date       2020-06-11
 * @author     Luis Machuca Bezzaza <lambalicious [at] tuta [dot] io>
 * 
 * This file and the files in syntax/ provide the syntax mode for the 
 * divalign2 plugin.
 * 
 * This work is a form from previous plugin (plugin:divalign)
 * by Jason Byrne. Check the wikipage for details.
 */ 

// must be run within DokuWiki
if(!defined('DOKU_INC')) die();
if(!defined('DOKU_PLUGIN')) die();
if(!defined('DW_LF')) define('DW_LF',"\n");
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');

/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class syntax_plugin_divalign2_common extends DokuWiki_Syntax_Plugin {

    function getSort() { 
        return 180; 
    }

    function getType() { 
        return 'formatting'; 
    }

    function getAllowedTypes() { 
        return array( 
         'container', 'substition', 'protected', 
         'disabled', 'formatting', 'paragraphs'
         );
    }

    function getPType() {
        return 'block';
    }
 
    function connectTo($mode) {
    }

    function postConnect() {
    }

    function handle($match, $state, $pos, Doku_Handler $handler){
        // unpack and process
        $content= $match['content'];
        $align= $match['align'];
        switch ( $state ) {
          case DOKU_LEXER_ENTER: {
            break;
            }
          case DOKU_LEXER_UNMATCHED: {
            $handler->_addCall('cdata', array($content), $pos);
            break;          
            }
        }
        return array($align,$state,$pos);
    }

    function render($mode, Doku_Renderer $renderer, $data) {
        list ($align, $state, $pos) = $data;
        if (false) {

        } else if (in_array($mode, ['xhtml', 's5', 'purplenumbers', 'rplus', 'html5'])) {
            
            switch ($state) {
            case DOKU_LEXER_ENTER: {                
                if ($align) { 
                    $renderer->doc .= '<p class="divalign-'.$align.'">'; 
                    }
                break;
            }
            case DOKU_LEXER_EXIT : {
                $renderer->doc .= '</p><!--divalign-->'. DW_LF;
                break;
            }
            } // end switch
            return true;

        } else if ($mode=='odt') {
            if (!method_exists ($renderer, 'getODTPropertiesFromElement')) {
                $this->render_odt_v1 ($renderer, $state, $align);
            } else {
                $this->render_odt_v2 ($renderer, $state, $align);
            }
            return true;
        }
        
        return false;
    }

    function render_odt_v1 (Doku_Renderer $renderer, $state, $align) {
        $Align = ucfirst ($align);
        //static $center_defined= false;
        $st = <<<EOF
<style:style style:name="Text.Divalign.$Align" style:display-name="Text.Divalign.$Align" style:family="paragraph" style:parent-style-name="Text_20_body">
<style:paragraph-properties fo:text-align="$align" style:justify-single-word="false" />
</style:style>

EOF;

        $renderer->autostyles["Text.Divalign.$Align"]= $st;
        $center_defined= true;
        switch ($state) {
        case DOKU_LEXER_ENTER: {
            $renderer->doc.= "<text:p text:style-name=\"Text.Divalign.$Align\">";
            break;
        }
        case DOKU_LEXER_EXIT: {
            $renderer->doc.= '</text:p>';
            //reduce_odt();
            break;
        }
        } // end switch
    }

    function render_odt_v2 (Doku_Renderer $renderer, $state, $align) {
        static $first = true;
        $alignments = array ('left', 'right', 'center', 'justify');
        
        if ($first) {
            // First entrance of the function. Create our ODT styles.
            // Group them under a parent called "Plugin DivAlign2"
            $first = false;

            // Create parent style to group the others beneath it
            if (!$renderer->styleExists('Plugin_DivAlign2')) {
                $parent_properties = array();
                $parent_properties ['style-parent'] = 'Text_20_body';
                $parent_properties ['style-class'] = 'Plugin_DivAlign2';
                $parent_properties ['style-name'] = 'Plugin_DivAlign2';
                $parent_properties ['style-display-name'] = 'Plugin DivAlign2';
                $renderer->createParagraphStyle($parent_properties);
            }

            $properties = array ();
            $properties ['justify-single-word'] = 'false';
            $properties ['style-class'] = NULL;
            $properties ['style-parent'] = 'Plugin_DivAlign2';
            foreach ($alignments as $alignment) {
                $Align = ucfirst ($alignment);
                $name = 'Plugin DivAlign2 '.$Align;
                $style_name = 'Plugin_DivAlign2_'.$Align;
                if (!$renderer->styleExists($style_name)) {
                    $properties ['style-name'] = $style_name;
                    $properties ['style-display-name'] = $name;
                    $properties ['text-align'] = $alignment;
                    $renderer->createParagraphStyle($properties);
                }
            }
        }

        $Align = ucfirst ($align);
        switch ($state) {
            case DOKU_LEXER_ENTER:
                $renderer->p_close();
                $renderer->p_open('Plugin_DivAlign2_'.$Align);
                break;
            case DOKU_LEXER_EXIT:
                $renderer->p_close();
                break;
        }
    }

} // end class


