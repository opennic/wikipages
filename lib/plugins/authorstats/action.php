<?php

/**
 * DokuWiki Plugin authorstats (Action Component)
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

class action_plugin_authorstats extends DokuWiki_Action_Plugin
{
    var $helpers = null;

    function __construct()
    {
        $this->helpers = $this->loadHelper("authorstats", true);
        if (!$this->helpers->statsFileExists())
            $this->_initializeData();
    }

    var $supportedModes = array("xhtml", "metadata");

    function register(Doku_Event_Handler $controller)
    {
        $controller->register_hook("ACTION_SHOW_REDIRECT", "BEFORE", $this, "_updateSavedStats");
        $controller->register_hook("PARSER_CACHE_USE", "BEFORE", $this, "_cachePrepare");
        $controller->register_hook("ACTION_ACT_PREPROCESS", "BEFORE",  $this, "_allow_show_author_pages");
        $controller->register_hook("TPL_ACT_UNKNOWN", "BEFORE",  $this, "_show_author_pages");
    }

    function _allow_show_author_pages(Doku_Event $event, $param)
    {
        if ($event->data != "authorstats_pages") return;
        $event->preventDefault();
    }

    function _show_author_pages(Doku_Event $event, $param)
    {
        if ($event->data != "authorstats_pages") return;
        $event->preventDefault();
        $flags = explode(",", str_replace(" ", "", $this->getConf("pagelist_flags")));
        $name  = hsc($_REQUEST["name"]);
        $usd = $this->helpers->readUserJSON($name);
        $ids = $usd["pages"][$_REQUEST["type"]];

        if ((!$pagelist = $this->loadHelper("pagelist"))) {
            return false;
        }

        /* @var helper_plugin_pagelist $pagelist */
        $pagelist->setFlags($flags);
        $pagelist->startList();
        foreach ($ids as $key => $value) {
            $page = array("id" => urldecode($key));
            $pagelist->addPage($page);
        }
        $type = "";
        switch ($_REQUEST["type"]) {
            case "C":
                $type = "Creates";
                break;
            case "E":
                $type = "Edits";
                break;
            case "e":
                $type = "Minor edits";
                break;
            case "D":
                $type = "Deletes";
                break;
            case "R":
                $type = "Reverts";
                break;
        }
        print "<h1>Pages[" . $type . "]: " . userlink($_REQUEST["name"], true) . "</h1>" . DOKU_LF;
        print "<div class=\"level1\">" . DOKU_LF;
        print $pagelist->finishList();
        print "</div>" . DOKU_LF;
    }

    function _initializeData()
    {
        global $conf;
        if ($conf['allowdebug']) $start_time = microtime(true);
        global $conf;
        $dir = $conf["metadir"] . "/";

        $this->helpers->createDirIfMissing("data");
        // Delete JSON files
        $lastchange = (-1 * PHP_INT_MAX) - 1;
        array_map("unlink", glob($this->helpers->basedir . "/*.json"));
        $sd = array();
        // Update everything
        $files = $this->_getChangeLogs($dir);
        foreach ($files as $file) {
            $this->_updateStats($file, $sd, $lastchange, false);
        }
        if ($conf['allowdebug']) {
            $end_time = microtime(true);
            $execution_time = ($end_time - $start_time);
            dbglog(__FUNCTION__ . " time:" . $execution_time, "AUTHORSTATS PLUGIN");
        }
    }

    // Updates the saved statistics by checking the last lines
    // in the /data/meta/ directory
    function _updateSavedStats(Doku_Event $event)
    {
        global $conf;
        if ($conf['allowdebug']) $start_time = microtime(true);

        // Read saved data from JSON file
        $sd = $this->helpers->readJSON();
        // Get last change 
        $lastchange = empty($sd) ?  (-1 * PHP_INT_MAX) - 1 : (int) $sd["lastchange"];
        $file = $this->_getChangesFileForPage($event->data["id"]);
        $this->_updateStats($file, $sd, $lastchange);
        if ($conf['allowdebug']) {
            $end_time = microtime(true);
            $execution_time = ($end_time - $start_time);
            dbglog(__FUNCTION__ . " time:" . $execution_time, "AUTHORSTATS PLUGIN");
        }
    }

    // If the page is no more recent than the modification of the JSON file, refresh the page.
    public function _cachePrepare(&$event, $param)
    {
        $cache = &$event->data;

        if (!isset($cache->page)) return;
        if (!isset($cache->mode) || !in_array($cache->mode, $this->supportedModes)) return;

        $enabled = p_get_metadata($cache->page, "authorstats-enabled");
        if (isset($enabled)) {
            if (@filemtime($cache->cache) < @filemtime($this->helpers->summaryfile)) {
                $event->preventDefault();
                $event->stopPropagation();
                $event->result = false;
            }
        }
    }

    function _getChangeLogs($dir, &$files = array())
    {
        $files = $this->helpers->rglob($dir . "[^_]*.changes", GLOB_NOSORT);
        return $files;
    }

    function _parseChange($line)
    {
        $record = array();
        $parts = explode(DOKU_TAB, $line);
        if ($parts && $parts[4] != "") {
            $record["timestamp"] = $parts[0];
            $record["type"] = $parts[2];
            $record["author"] = $parts[4];
            $record["date"] = date("Ym", $parts[0]);
        }
        return $record;
    }

    function _getChangesFileForPage($page_id)
    {
        global $conf;
        $page = preg_replace("[:]", "/", $page_id);
        return $conf["metadir"] . "/" . $page . ".changes";
    }

    function _updateStats($change_file, &$sd, &$lastchange, $skip = true)
    {
        global $conf;
        $metadir = $conf["metadir"] . "/";
        $newlast = $lastchange;
        if (is_readable($change_file))
            $file_contents = array_reverse(file($change_file));
        else {
            dbglog("ERROR: " . __FUNCTION__ . " - Couldn't open file:" . var_export($change_file, true), "AUTHORSTATS PLUGIN");
            return;
        }

        foreach ($file_contents as $line) {
            $r = $this->_parseChange($line);

            if ($r["author"] == "")
                continue;

            if ($r["timestamp"] <= $lastchange && $skip)
                break;

            // Update the last if there is a more recent change
            $newlast = max($newlast, $r["timestamp"]);

            // If the author is not in the array, initialize their stats
            if (!isset($sd["authors"][$r["author"]])) {
                $sd["authors"][$r["author"]]["C"] = 0;
                $sd["authors"][$r["author"]]["E"] = 0;
                $sd["authors"][$r["author"]]["e"] = 0;
                $sd["authors"][$r["author"]]["D"] = 0;
                $sd["authors"][$r["author"]]["R"] = 0;
                $sd["authors"][$r["author"]]["pm"] = array();
            } else {
                // Initialize month if doesn't exist
                // else increment it
                if (!isset($sd["authors"][$r["author"]]["pm"][$r["date"]]))
                    $sd["authors"][$r["author"]]["pm"][$r["date"]] = 1;
                else
                    $sd["authors"][$r["author"]]["pm"][$r["date"]]++;
            }
            $sd["authors"][$r["author"]][$r["type"]]++;
            $usd = $this->helpers->readUserJSON($r["author"]);
            $key = str_replace($metadir, "", $change_file);
            $key = str_replace(".changes", "", $key);
            $key = str_replace("/", ":", $key);
            $usd["pages"][$r["type"]][$key] = 1;
            $this->helpers->saveUserJSON($r["author"], $usd);
        }
        $lastchange = $newlast;
        $sd["lastchange"] = $newlast;
        $this->helpers->saveJSON($sd);
    }
}
