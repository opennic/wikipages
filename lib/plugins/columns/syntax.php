<?php

/**
 * Plugin Columns: Syntax & rendering
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Mykola Ostrovskyy <dwpforge@gmail.com>
 *             Based on plugin by Michael Arlt <michael.arlt [at] sk-schwanstetten [dot] de>
 */

/* Must be run within Dokuwiki */
if(!defined('DOKU_INC')) die();

if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN', DOKU_INC . 'lib/plugins/');
require_once(DOKU_PLUGIN . 'syntax.php');

class syntax_plugin_columns extends DokuWiki_Syntax_Plugin {

    private $mode;
    private $lexerSyntax;
    private $syntax;
    private $xhtmlRenderer;
    private $odtRenderer;

    /**
     * Constructor
     */
    public function __construct() {
        $this->mode = substr(get_class($this), 7);

        $columns = $this->getColumnsTagName();
        $newColumn = $this->getNewColumnTagName();
        if ($this->getConf('wrapnewcol') == 1) {
            $newColumnLexer = '<' . $newColumn . '(?:>|\s.*?>)';
            $newColumnHandler = '<' . $newColumn . '(.*?)>';
        }
        else {
            $newColumnLexer = $newColumn;
            $newColumnHandler = $newColumn;
        }
        $enterLexer = '<' . $columns . '(?:>|\s.*?>)';
        $enterHandler = '<' . $columns . '(.*?)>';
        $exit = '<\/' . $columns . '>';

        $this->lexerSyntax['enter'] = $enterLexer;
        $this->lexerSyntax['newcol'] = $newColumnLexer;
        $this->lexerSyntax['exit'] = $exit;

        $this->syntax[DOKU_LEXER_ENTER] = '/' . $enterHandler . '/';
        $this->syntax[DOKU_LEXER_MATCHED] = '/' . $newColumnHandler . '/';
        $this->syntax[DOKU_LEXER_EXIT] = '/' . $exit . '/';
    }

    /**
     * What kind of syntax are we?
     */
    public function getType() {
        return 'substition';
    }

    public function getPType() {
        return 'block';
    }

    /**
     * Where to sort in?
     */
    public function getSort() {
        return 65;
    }

    public function connectTo($mode) {
        $this->Lexer->addSpecialPattern($this->lexerSyntax['enter'], $mode, $this->mode);
        $this->Lexer->addSpecialPattern($this->lexerSyntax['newcol'], $mode, $this->mode);
        $this->Lexer->addSpecialPattern($this->lexerSyntax['exit'], $mode, $this->mode);
    }

    /**
     * Handle the match
     */
    public function handle($match, $state, $pos, Doku_Handler $handler) {
        foreach ($this->syntax as $state => $pattern) {
            if (preg_match($pattern, $match, $data) == 1) {
                break;
            }
        }
        switch ($state) {
            case DOKU_LEXER_ENTER:
            case DOKU_LEXER_MATCHED:
                return array($state, preg_split('/\s+/', $data[1], -1, PREG_SPLIT_NO_EMPTY));

            case DOKU_LEXER_EXIT:
                return array($state, array());
        }
        return false;
    }

    /**
     * Create output
     */
    public function render($mode, Doku_Renderer $renderer, $data) {
        $columnsRenderer = $this->getRenderer($mode, $renderer);

        if ($columnsRenderer != NULL) {
            $columnsRenderer->render($data[0], $renderer, $data[1]);
            return true;
        }
        return false;
    }

    /**
     *
     */
    private function getRenderer($mode, Doku_Renderer $renderer) {
        switch ($mode) {
            case 'xhtml':
                if ($this->xhtmlRenderer == NULL) {
                    $this->xhtmlRenderer = new columns_renderer_xhtml();
                }
                return $this->xhtmlRenderer;

            case 'odt':
                if ($this->odtRenderer == NULL) {
                    if (method_exists($renderer, 'getODTPropertiesFromElement')) {
                        $this->odtRenderer = new columns_renderer_odt_v2();
                    }
                    else {
                        $this->odtRenderer = new columns_renderer_odt_v1();
                    }
                }
                return $this->odtRenderer;
        }

        return NULL;
    }

    /**
     * Returns columns tag
     */
    private function getColumnsTagName() {
        $tag = $this->getConf('kwcolumns');
        if ($tag == '') {
            $tag = $this->getLang('kwcolumns');
        }
        return $tag;
    }

    /**
     * Returns new column tag
     */
    private function getNewColumnTagName() {
        $tag = $this->getConf('kwnewcol');
        if ($tag == '') {
            $tag = $this->getLang('kwnewcol');
        }
        return $tag;
    }
}

/**
 * Base class for columns rendering.
 */
abstract class columns_renderer {
    /**
     *
     */
    public function render($state, Doku_Renderer $renderer, $attribute) {
        switch ($state) {
            case DOKU_LEXER_ENTER:
                $this->render_enter($renderer, $attribute);
                break;

            case DOKU_LEXER_MATCHED:
                $this->render_matched($renderer, $attribute);
                break;

            case DOKU_LEXER_EXIT:
                $this->render_exit($renderer, $attribute);
                break;
        }
    }

    abstract protected function render_enter(Doku_Renderer $renderer, $attribute);
    abstract protected function render_matched(Doku_Renderer $renderer, $attribute);
    abstract protected function render_exit(Doku_Renderer $renderer, $attribute);

    /**
     *
     */
    protected function getAttribute($attribute, $name) {
        $result = '';
        if (array_key_exists($name, $attribute)) {
            $result = $attribute[$name];
        }
        return $result;
    }

    /**
     *
     */
    protected function getStyle($attribute, $attributeName, $styleName = '') {
        $result = $this->getAttribute($attribute, $attributeName);
        if ($result != '') {
            if ($styleName == '') {
                $styleName = $attributeName;
            }
            $result = $styleName . ':' . $result . ';';
        }
        return $result;
    }
}

/**
 * Class columns_renderer_xhtml
 * @author LarsDW223
 */
class columns_renderer_xhtml extends columns_renderer {
    /**
     *
     */
    public function render($state, Doku_Renderer $renderer, $attribute) {
        parent::render($state, $renderer, $attribute);

        if ($state == 987 && method_exists($renderer, 'finishSectionEdit')) {
            $renderer->finishSectionEdit($attribute);
        }
    }

    /**
     *
     */
    protected function render_enter(Doku_Renderer $renderer, $attribute) {
        $renderer->doc .= $this->renderTable($attribute) . DOKU_LF;
        $renderer->doc .= '<tr>' . $this->renderTd($attribute) . DOKU_LF;
    }

    /**
     *
     */
    protected function render_matched(Doku_Renderer $renderer, $attribute) {
        $renderer->doc .= '</td>' . $this->renderTd($attribute) . DOKU_LF;
    }

    /**
     *
     */
    protected function render_exit(Doku_Renderer $renderer, $attribute) {
        $renderer->doc .= '</td></tr></table>' . DOKU_LF;
    }

    /**
     *
     */
    private function renderTable($attribute) {
        $width = $this->getAttribute($attribute, 'table-width');
        if ($width != '') {
            return '<table class="columns-plugin" style="width:' . $width . '">';
        }
        else {
            return '<table class="columns-plugin">';
        }
    }

    /**
     *
     */
    private function renderTd($attribute) {
        $class[] = 'columns-plugin';
        $class[] = $this->getAttribute($attribute, 'class');
        $class[] = $this->getAttribute($attribute, 'text-align');
        $html = '<td class="' . implode(' ', array_filter($class)) . '"';
        $style = $this->getStyle($attribute, 'column-width', 'width');
        $style .= $this->getStyle($attribute, 'vertical-align');
        if ($style != '') {
            $html .= ' style="' . $style . '"';
        }
        return $html . '>';
    }
}

/**
 * Class columns_renderer_odt_v1
 */
class columns_renderer_odt_v1 extends columns_renderer {
    /**
     *
     */
    protected function render_enter(Doku_Renderer $renderer, $attribute) {
        $this->addOdtTableStyle($renderer, $attribute);
        $this->addOdtColumnStyles($renderer, $attribute);
        $this->renderOdtTableEnter($renderer, $attribute);
        $this->renderOdtColumnEnter($renderer, $attribute);
    }

    /**
     *
     */
    protected function render_matched(Doku_Renderer $renderer, $attribute) {
        $this->addOdtColumnStyles($renderer, $attribute);
        $this->renderOdtColumnExit($renderer);
        $this->renderOdtColumnEnter($renderer, $attribute);
    }

    /**
     *
     */
    protected function render_exit(Doku_Renderer $renderer, $attribute) {
        $this->renderOdtColumnExit($renderer);
        $this->renderOdtTableExit($renderer);
    }

    /**
     *
     */
    private function addOdtTableStyle(Doku_Renderer $renderer, $attribute) {
        $styleName = $this->getOdtTableStyleName($this->getAttribute($attribute, 'block-id'));
        $style = '<style:style style:name="' . $styleName . '" style:family="table">';
        $style .= '<style:table-properties';
        $width = $this->getAttribute($attribute, 'table-width');

        if (($width != '') && ($width != '100%')) {
            $metrics = $this->getOdtMetrics($renderer->autostyles);
            $style .= ' style:width="' . $this->getOdtAbsoluteWidth($metrics, $width) . '"';
        }
        $align = ($width == '100%') ? 'margins' : 'left';
        $style .= ' table:align="' . $align . '"/>';
        $style .= '</style:style>';

        $renderer->autostyles[$styleName] = $style;
    }

    /**
     *
     */
    private function addOdtColumnStyles(Doku_Renderer $renderer, $attribute) {
        $blockId = $this->getAttribute($attribute, 'block-id');
        $columnId = $this->getAttribute($attribute, 'column-id');
        $styleName = $this->getOdtTableStyleName($blockId, $columnId);

        $style = '<style:style style:name="' . $styleName . '" style:family="table-column">';
        $style .= '<style:table-column-properties';
        $width = $this->getAttribute($attribute, 'column-width');

        if ($width != '') {
            $metrics = $this->getOdtMetrics($renderer->autostyles);
            $style .= ' style:column-width="' . $this->getOdtAbsoluteWidth($metrics, $width) . '"';
        }
        $style .= '/>';
        $style .= '</style:style>';

        $renderer->autostyles[$styleName] = $style;

        $styleName = $this->getOdtTableStyleName($blockId, $columnId, 1);

        $style = '<style:style style:name="' . $styleName . '" style:family="table-cell">';
        $style .= '<style:table-cell-properties';
        $style .= ' fo:border="none"';
        $style .= ' fo:padding-top="0cm"';
        $style .= ' fo:padding-bottom="0cm"';

        switch ($this->getAttribute($attribute, 'class')) {
            case 'first':
                $style .= ' fo:padding-left="0cm"';
                $style .= ' fo:padding-right="0.4cm"';
                break;

            case 'last':
                $style .= ' fo:padding-left="0.4cm"';
                $style .= ' fo:padding-right="0cm"';
                break;
        }

        /* There seems to be no easy way to control horizontal alignment of text within
           the column as fo:text-align aplies to individual paragraphs. */
        //TODO: $this->getAttribute($attribute, 'text-align');

        $align = $this->getAttribute($attribute, 'vertical-align');
        if ($align != '') {
            $style .= ' style:vertical-align="' . $align . '"';
        }
        else {
            $style .= ' style:vertical-align="top"';
        }

        $style .= '/>';
        $style .= '</style:style>';

        $renderer->autostyles[$styleName] = $style;
    }

    /**
     *
     */
    private function renderOdtTableEnter(Doku_Renderer $renderer, $attribute) {
        $columns = $this->getAttribute($attribute, 'columns');
        $blockId = $this->getAttribute($attribute, 'block-id');
        $styleName = $this->getOdtTableStyleName($blockId);

        $renderer->doc .= '<table:table table:style-name="' . $styleName . '">';
        for ($c = 0; $c < $columns; $c++) {
            $styleName = $this->getOdtTableStyleName($blockId, $c + 1);
            $renderer->doc .= '<table:table-column table:style-name="' . $styleName . '" />';
        }
        $renderer->doc .= '<table:table-row>';
    }

    /**
     *
     */
    private function renderOdtColumnEnter(Doku_Renderer $renderer, $attribute) {
        $blockId = $this->getAttribute($attribute, 'block-id');
        $columnId = $this->getAttribute($attribute, 'column-id');
        $styleName = $this->getOdtTableStyleName($blockId, $columnId, 1);
        $renderer->doc .= '<table:table-cell table:style-name="' . $styleName . '" office:value-type="string">';
    }

    /**
     *
     */
    private function renderOdtColumnExit(Doku_Renderer $renderer) {
        $renderer->doc .= '</table:table-cell>';
    }

    /**
     *
     */
    private function renderOdtTableExit(Doku_Renderer $renderer) {
        $renderer->doc .= '</table:table-row>';
        $renderer->doc .= '</table:table>';
    }

    /**
     * Convert relative units to absolute
     */
    private function getOdtAbsoluteWidth($metrics, $width) {
        if (preg_match('/([\d\.]+)(.+)/', $width, $match) == 1) {
            switch ($match[2]) {
                case '%':
                    /* Won't work for nested column blocks */
                    $width = ($match[1] / 100 * $metrics['page-width']) . $metrics['page-width-units'];
                    break;
                case 'em':
                    /* Rough estimate */
                    $width = ($match[1] * 0.8 * $metrics['font-size']) . $metrics['font-size-units'];
                    break;
            }
        }
        return $width;
    }

    /**
     *
     */
    private function getOdtTableStyleName($blockId, $columnId = 0, $cell = 0) {
        $result = 'ColumnsBlock' . $blockId;
        if ($columnId != 0) {
            if ($columnId <= 26) {
                $result .= '.' . chr(ord('A') + $columnId - 1);
            }
            else {
                /* To unlikey to handle it properly */
                $result .= '.a';
            }
            if ($cell != 0) {
                $result .= $cell;
            }
        }
        return $result;
    }

    /**
     *
     */
    private function getOdtMetrics($autoStyle) {
        $result = array();
        if (array_key_exists('pm1', $autoStyle)) {
            $style = $autoStyle['pm1'];
            if (preg_match('/fo:page-width="([\d\.]+)(.+?)"/', $style, $match) == 1) {
                $result['page-width'] = floatval($match[1]);
                $result['page-width-units'] = $match[2];
                $units = $match[2];

                if (preg_match('/fo:margin-left="([\d\.]+)(.+?)"/', $style, $match) == 1) {
                    // TODO: Unit conversion
                    if ($match[2] == $units) {
                        $result['page-width'] -= floatval($match[1]);
                    }
                }
                if (preg_match('/fo:margin-right="([\d\.]+)(.+?)"/', $style, $match) == 1) {
                    if ($match[2] == $units) {
                        $result['page-width'] -= floatval($match[1]);
                    }
                }
            }
        }
        if (!array_key_exists('page-width', $result)) {
            $result['page-width'] = 17;
            $result['page-width-units'] = 'cm';
        }

        /* There seems to be no easy way to get default font size apart from loading styles.xml. */
        $styles = io_readFile(DOKU_PLUGIN . 'odt/styles.xml');
        if (preg_match('/<style:default-style style:family="paragraph">(.+?)<\/style:default-style>/s', $styles, $match) == 1) {
            if (preg_match('/<style:text-properties(.+?)>/', $match[1], $match) == 1) {
                if (preg_match('/fo:font-size="([\d\.]+)(.+?)"/', $match[1], $match) == 1) {
                    $result['font-size'] = floatval($match[1]);
                    $result['font-size-units'] = $match[2];
                }
            }
        }
        if (!array_key_exists('font-size', $result)) {
            $result['font-size'] = 12;
            $result['font-size-units'] = 'pt';
        }
        return $result;
    }
}

/**
 * Class columns_renderer_odt_v2
 * @author LarsDW223
 */
class columns_renderer_odt_v2 extends columns_renderer {
    /**
     *
     */
    protected function render_enter(Doku_Renderer $renderer, $attribute) {
        $this->renderOdtTableEnter($renderer, $attribute);
        $this->renderOdtColumnEnter($renderer, $attribute);
    }

    /**
     *
     */
    protected function render_matched(Doku_Renderer $renderer, $attribute) {
        $this->renderOdtColumnExit($renderer);
        $this->renderOdtColumnEnter($renderer, $attribute);
    }

    /**
     *
     */
    protected function render_exit(Doku_Renderer $renderer, $attribute) {
        $this->renderOdtColumnExit($renderer);
        $this->renderOdtTableExit($renderer);
    }

    /**
     *
     */
    private function renderOdtTableEnter(Doku_Renderer $renderer, $attribute) {
        $properties = array();
        $properties ['width'] = $this->getAttribute($attribute, 'table-width');
        $properties ['align'] = 'left';
        $renderer->_odtTableOpenUseProperties ($properties);
        $renderer->tablerow_open();
    }

    /**
     *
     */
    private function renderOdtColumnEnter(Doku_Renderer $renderer, $attribute) {
        $properties = array();
        $properties ['width'] = $this->getAttribute($attribute, 'column-width');
        $properties ['border'] = 'none';
        $properties ['padding-top'] = '0cm';
        $properties ['padding-bottom'] = '0cm';
        switch ($this->getAttribute($attribute, 'class')) {
            case 'first':
                $properties ['padding-left'] = '0cm';
                $properties ['padding-right'] = '0.4cm';
                break;

            case 'last':
                $properties ['padding-left'] = '0.4cm';
                $properties ['padding-right'] = '0cm';
                break;
        }
        $align = $this->getAttribute($attribute, 'vertical-align');
        if ($align != '') {
            $properties ['vertical-align'] = $align;
        }
        else {
            $properties ['vertical-align'] = 'top';
        }
        $align = $this->getAttribute($attribute, 'text-align');
        if ($align != '') {
            $properties ['text-align'] = $align;
        }
        else {
            $properties ['text-align'] = 'left';
        }

        $renderer->_odtTableCellOpenUseProperties($properties);
    }

    /**
     *
     */
    private function renderOdtColumnExit(Doku_Renderer $renderer) {
        $renderer->tablecell_close();
    }

    /**
     *
     */
    private function renderOdtTableExit(Doku_Renderer $renderer) {
        $renderer->tablerow_close();
        $renderer->table_close();
    }
}
