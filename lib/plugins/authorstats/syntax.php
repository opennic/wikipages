<?php
/**
 * DokuWiki Plugin authorstats (Syntax Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  George Chatzisofroniou <sophron@latthi.com>
 * @author  Constantinos Xanthopoulos <conx@xanthopoulos.info>
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

if (!defined('DOKU_LF')) define('DOKU_LF', "\n");
if (!defined('DOKU_TAB')) define('DOKU_TAB', "\t");
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');

require_once DOKU_PLUGIN.'syntax.php';
require_once DOKU_PLUGIN.'authorstats/helpers.php';

class syntax_plugin_authorstats extends DokuWiki_Syntax_Plugin 
{
    public function getType() 
    {
        return 'substition';
    }

    public function getPType() 
    {
        return 'stack';
    } 

    public function getSort() 
    {
        return 371;
    } 

    public function connectTo($mode) 
    {
        $this->Lexer->addSpecialPattern('<AUTHORSTATS>',$mode,'plugin_authorstats');
        $this->Lexer->addSpecialPattern('<AUTHORSTATS [0-9]+>',$mode,'plugin_authorstats');
        $this->Lexer->addSpecialPattern('<AUTHORSTATS YEARGRAPH>',$mode,'plugin_authorstats');
    }

    public function handle($match, $state, $pos, Doku_Handler $handler)
    {
        return array($match);
    }

    public function render($mode, Doku_Renderer $renderer, $data) 
    {

        if ($mode == "metadata") 
        {
            $renderer->meta['authorstats-enabled'] = 1;
            return true;
        }

        if($mode == 'xhtml') 
        {
            if (preg_match("/<AUTHORSTATS (?P<months>[0-9]+)>/", $data[0], $matches)) 
            {
                $renderer->doc .= $this->_getMonthlyStatsTable(intval($matches[1]));
            }
            else if (preg_match("/<AUTHORSTATS YEARGRAPH>/", $data[0], $matches))
            {
                $renderer->doc .= $this->getYearGraph();
            }
            else 
            {
                $renderer->doc .= $this->_getStatsTable();
            }
        }
    }

    // Returns the number of author's Contrib for a number of months
    function _getLastMonthsContrib($author, $months) 
    {
        $m = Array();
        $sum = 0;
        // Get an array of months in the format used eg. 201208, 201209, 201210
        for ($i=$months-1; $i>=0; $i--) 
            array_push($m, date("Ym", strtotime("-".$i." Months")));
        
        // Sum the Contrib
        foreach ($m as $month) 
        {
            if (array_key_exists($month, $author["pm"])) 
            {
                $sum += intval($author["pm"][$month]);
            }
        }
        return $sum;
    }

    function _sortByContrib($a, $b) 
    {
        return $this->_getTotalContrib($a) <= $this->_getTotalContrib($b) ? 1 : -1 ;
    }

    function _getTotalContrib($a)
    {
        return (intval($a["C"]) + intval($a["E"]) + intval($a["e"]) + intval($a["D"]) + intval($a["R"]));
    }

    function _sortByLastMonthsContrib($a, $b) 
    {
        return $a['lmc'] >= $b['lmc'] ? -1 : 1;
    } 

    function _getMonthlyContrib($authors, $yearmonth)
    {
        $sum = 0;
        foreach ($authors as $author)
        {
            if (array_key_exists($yearmonth, $author["pm"])) 
            {
                $sum += intval($author["pm"][$yearmonth]);
            }
        }
        return $sum;
    }

    function getYearGraph()
    {
        $output = "<h3>Yearly Contributions</h3>";
        $authors = authorstatsReadJSON();
        $authors = $authors["authors"];
        if (!$authors) return "There are no stats to output!";
        $totalpm = Array();
        $months = Array("January", "February", "March", "April","May","June","July","August","September","October", "November", "December");
        for ($i=1; $i <= 12; $i++)
        {
            array_push($totalpm, $this->_getMonthlyContrib($authors, date("Y").sprintf("%02s", $i))); 
        }
        // Append the parameters for the Axes Titles
        $url = "https://chart.googleapis.com/chart?cht=bhs&chs=600x400&chxt=y,y,x,x&chco=0000F0&chxl=0:|January|February|March|April|May|June|July|August|September|October|November|December|1:|Months|3:|Contributions&chxr=0,1,12|1,0,100|3,0,100&chxp=1,2,3,4,5,6,7,8,9,10,11,12|1,50|3,50&chds=a&chd=t:".implode(",",array_reverse($totalpm));
        return $output."<img src=\"".$url."\">";
    }
    
    // Returns the HTML table with the authors and their stats
    function _getStatsTable() 
    {   
        $output = " <h3>General Statistics</h3><table class=\"authorstats-table\"><tr><th>Name</th><th>Creates</th><th>Edits</th><th>Minor edits</th><th>Deletes</th><th>Reverts</th><th>Contrib</th></tr>";
        $authors = authorstatsReadJSON();
        $authors = $authors["authors"];
        if (!$authors) return "There are no stats to output!";
        uasort($authors, array($this, '_sortByContrib'));
        foreach ($authors as $name => $author) 
        {
            $output .= "<tr><th>" . 
            $name . "</th><td>" . 
            $author['C'] . "</td><td>" . 
            $author['E'] .  "</td><td>" . 
            $author['e'] . "</td><td>" . 
            $author['D'] . "</td><td>" . 
            $author['R'] . "</td><td>" . 
            strval($this->_getTotalContrib($author))."</td></tr>";
        }
        $output .= "</table>";
        return $output;
    }

    // Returns the HTML table with the authors and their Contrib for the 
    // last <$months> months
    function _getMonthlyStatsTable($months) 
    {   
        $output = "<h3>Contribution in the last ".$months." months</h3><table class=\"authorstats-table\"><tr><th>Name</th><th>Contrib</th></tr>";
        $authors = authorstatsReadJSON();
        $authors = $authors["authors"];
        if (!$authors) return "There are no stats to output!";
        foreach($authors as $name=>$author) 
        {
            $authors[$name]['lmc'] = $this->_getLastMonthsContrib($author, $months);
        } 
        uasort($authors, array($this, '_sortByLastMonthsContrib'));
        foreach ($authors as $name=>$author) 
        {
            if ($authors[$name]['lmc'] > 0 ) {
                $output .= "<tr><th>" . 
                $name . "</th><td>" . 
                strval($authors[$name]['lmc']) . "</td></tr>";
            }
        }
        $output .= "</table>";
        return $output;
    }
}
// vim:ts=4:sw=4:et:
