<?php

/**
 * DokuWiki Plugin authorstats (Syntax Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  George Chatzisofroniou <sophron@latthi.com>
 * @author  Constantinos Xanthopoulos <conx@xanthopoulos.info>
 */

// must be run within Dokuwiki
if (!defined("DOKU_INC")) die();
if (!defined("DOKU_LF")) define("DOKU_LF", "\n");
if (!defined("DOKU_TAB")) define("DOKU_TAB", "\t");
if (!defined("DOKU_PLUGIN")) define("DOKU_PLUGIN", DOKU_INC . "lib/plugins/");

class syntax_plugin_authorstats extends DokuWiki_Syntax_Plugin
{
    var $helpers = null;

    public function __construct()
    {
        $this->helpers = $this->loadHelper("authorstats", true);
    }

    public function getType()
    {
        return "substition";
    }

    public function getPType()
    {
        return "stack";
    }

    public function getSort()
    {
        return 371;
    }

    public function connectTo($mode)
    {
        $this->Lexer->addSpecialPattern("<AUTHORSTATS>", $mode, "plugin_authorstats");
        $this->Lexer->addSpecialPattern("<AUTHORSTATS [0-9]+>", $mode, "plugin_authorstats");
        $this->Lexer->addSpecialPattern("<AUTHORSTATS YEARGRAPH>", $mode, "plugin_authorstats");
        $this->Lexer->addSpecialPattern("<AUTHORSTATS YEARGRAPH\s+\d*\s*\w*>", $mode, "plugin_authorstats");
    }

    public function handle($match, $state, $pos, Doku_Handler $handler)
    {
        return array($match);
    }

    public function render($mode, Doku_Renderer $renderer, $data)
    {

        if ($mode == "metadata") {
            $renderer->meta["authorstats-enabled"] = 1;
            return true;
        }

        if ($mode == "xhtml") {
            if (preg_match("/<AUTHORSTATS (?P<months>[0-9]+)>/", $data[0], $matches)) {
                $renderer->doc .= $this->_getMonthlyStatsTable(intval($matches[1]));
            } else if (preg_match("/<AUTHORSTATS YEARGRAPH\s*(?P<years>[0-9]+)*\s*(?P<sort>(:?asc|ascending|desc|descending|rev|reverse)*)>/", $data[0], $matches)) {
                $renderer->doc .= $this->getYearGraph($matches);
            } else {
                $renderer->doc .= $this->_getStatsTable();
            }
        }
    }

    // Returns the number of author"s Contrib for a number of months
    function _getLastMonthsContrib($author, $months)
    {
        $m = array();
        $sum = 0;
        // Get an array of months in the format used eg. 201208, 201209, 201210
        for ($i = $months - 1; $i >= 0; $i--)
            array_push($m, date("Ym", strtotime("-" . $i . " Months")));

        // Sum the Contrib
        foreach ($m as $month) {
            if (array_key_exists($month, $author["pm"])) {
                $sum += intval($author["pm"][$month]);
            }
        }
        return $sum;
    }

    function _sortByContrib($a, $b)
    {
        return $this->_getTotalContrib($a) <= $this->_getTotalContrib($b) ? 1 : -1;
    }

    function _getTotalContrib($a)
    {
        return (intval($a["C"]) + intval($a["E"]) + intval($a["e"]) + intval($a["D"]) + intval($a["R"]));
    }

    function _sortByLastMonthsContrib($a, $b)
    {
        return $a["lmc"] >= $b["lmc"] ? -1 : 1;
    }

    function _getMonthlyContrib($authors, $yearmonth)
    {
        $sum = 0;
        foreach ($authors as $author) {
            if (array_key_exists($yearmonth, $author["pm"])) {
                $sum += intval($author["pm"][$yearmonth]);
            }
        }
        return $sum;
    }

    function getYearGraph($inopts)
    {
        global $conf;
        if ($conf['allowdebug']) $start_time = microtime(true);
        $output = "<h3>" . $this->getLang("yearly-contrib") . "</h3>";
        $data = $this->helpers->readJSON();
        $authors = $data["authors"];
        if (!$authors) return $this->getLang("no-stats");
        $totalpm = array();
        $labels = array();

        $max_months = 12;
        if (isset($inopts["years"]) && $inopts["years"] > 0) {
            $max_months = 12 * $inopts["years"];
        }
        for ($i = 0; $i <= $max_months; $i++) {
            array_push($totalpm, $this->_getMonthlyContrib($authors, date("Ym", strtotime("-$i months"))));
            array_push($labels,  date("Y-M", strtotime("-$i months")));
        }

        $totalpm = array_reverse($totalpm);       // For some odd reason the charting tool needs this is the reverse order of the labels...
        if (isset($inopts["sort"])) {
            if (preg_match("/^(desc|descending|rev|reverse)$/", $inopts["sort"])) {    // Reverse the sort order from the default
                $totalpm = array_reverse($totalpm);
                $labels  = array_reverse($labels);
            }
        }

        // Append the parameters for the Axes Titles
        $url  = "https://chart.googleapis.com/chart";
        $url .= "?cht=bhs";                                                                // Chart type; https://developers.google.com/chart/image/docs/gallery/chart_gall
        $url .= "&chs=500x600";                                                            // Chart size (width x height); The overall size is very limited, max total pixel has to be less than 300k.
        $url .= "&chxt=y,y,x,x";                                                           // Visible axes
        $url .= "&chco=0000F0";                                                            // Series colors
        $url .= "&chds=a";                                                                 // Scale for text format with custom range; a == automatic scaling
        $url .= "&chbh=a";                                                                 // Bar Width and Spacing; a == bars will resize to fit in the chart
        $url .= "&chxr=0,1,12|1,0,100|3,0,100";                                            // Axis ranges
        $url .= "&chxp=1,2,3,4,5,6,7,8,9,10,11,12|1,50|3,50";                              // Axis label positions
        $url .= "&chxl=0:|" . implode("|", $labels) . "|1:|Yr-Mon|3:|Num_of_Contributions";  // Axis labels
        $url .= "&chd=t:" . implode(",", $totalpm);                                           // Chart data string
        if ($conf['allowdebug']) {
            $end_time = microtime(true);
            $execution_time = ($end_time - $start_time);
            dbglog(__FUNCTION__ . " time:" . $execution_time, "AUTHORSTATS PLUGIN");
        }
        return $output . "<img src=\"" . $url . "\">";
    }

    function _makeAuthorLink($author, $name, $type)
    {
        if (!$this->getConf("enable-pagelist")) {
            return $author[$type];
        }
        $url = wl("authorstats:" . $name, array("do" => "authorstats_pages", "name" => $name, "type" => $type));
        $link = array(
            "href" => $url,
            "class" => "wikilink1",
            "tooltip" => hsc($name),
            "title" => hsc($author[$type])
        );
        $link = "<a href='" . $link["href"] . "' class='" . $link["class"] . "' title='" . $link["tooltip"] . "' rel='tag'>" . $link["title"] . "</a>";
        return $link;
    }

    // Returns the HTML table with the authors and their stats
    function _getStatsTable()
    {
        global $conf;
        if ($conf['allowdebug']) $start_time = microtime(true);
        $output = "<h3>" . $this->getLang("gen-stats") . "</h3><table class=\"authorstats-table\"><tr><th>" . $this->getLang("name") . "</th><th>" . $this->getLang("creates") . "</th><th>" . $this->getLang("edits") . "</th><th>" . $this->getLang("minor") . "</th><th>" . $this->getLang("deletes") . "</th><th>" . $this->getLang("reverts") . "</th><th>" . $this->getLang("contrib") . "</th></tr>";
        $authors = $this->helpers->readJSON();
        $authors = $authors["authors"];
        if (!$authors) return  $this->getLang("no-stats");
        uasort($authors, array($this, "_sortByContrib"));
        foreach ($authors as $name => $author) {
            $dname = $this->_getUser($name);
            if ($dname == null) continue;
            $output .= "<tr><th>" .
                $dname . "</th><td>" .
                $this->_makeAuthorLink($author, $name, "C") . "</td><td>" .
                $this->_makeAuthorLink($author, $name, "E") . "</td><td>" .
                $this->_makeAuthorLink($author, $name, "e") . "</td><td>" .
                $this->_makeAuthorLink($author, $name, "D") . "</td><td>" .
                $this->_makeAuthorLink($author, $name, "R") . "</td><td>" .
                strval($this->_getTotalContrib($author)) . "</td></tr>";
        }
        $output .= "</table>";
        if ($conf['allowdebug']) {
            $end_time = microtime(true);
            $execution_time = ($end_time - $start_time);
            dbglog(__FUNCTION__ . " time:" . $execution_time, "AUTHORSTATS PLUGIN");
        }
        return $output;
    }

    // Returns the HTML table with the authors and their Contrib for the
    // last <$months> months
    function _getMonthlyStatsTable($months)
    {
        global $conf;
        if ($conf['allowdebug']) $start_time = microtime(true);
        $output = "<h3>" . $this->getLang("contrib-months") . " " . $months . " " . $this->getLang("months") . "</h3><table class=\"authorstats-table\"><tr><th>" . $this->getLang("name") . "</th><th>" . $this->getLang("contrib") . "</th></tr>";
        $authors = $this->helpers->readJSON();
        $authors = $authors["authors"];
        if (!$authors) return  $this->getLang("no-stats");
        foreach ($authors as $name => $author) {
            $authors[$name]["lmc"] = $this->_getLastMonthsContrib($author, $months);
        }
        uasort($authors, array($this, "_sortByLastMonthsContrib"));
        foreach ($authors as $name => $author) {
            if ($authors[$name]["lmc"] > 0) {
                $dname = $this->_getUser($name);
                if ($dname == null) continue;
                $output .= "<tr><th>" .
                    $dname . "</th><td>" .
                    strval($authors[$name]["lmc"]) . "</td></tr>";
            }
        }
        $output .= "</table>";
        if ($conf['allowdebug']) {
            $end_time = microtime(true);
            $execution_time = ($end_time - $start_time);
            dbglog(__FUNCTION__ . " time:" . $execution_time, "AUTHORSTATS PLUGIN");
        }
        return $output;
    }

    function _getUser($name)
    {
        global $auth;
        $user = $auth->getUserData($name);
        if ($user !== false and $this->getConf("show-realname")) {
            $dname = $user["name"];
        } else if ($this->getConf("show-profile-links")) {
        } else if ($user !== false) {
            $dname = $name;
        } else {
            // Deleted user?
            if (!$this->getConf("show-deleted-users")) return null;
            $dname = "<i>($name)</i>";
        }
        return $dname;
    }
}
