<?php
/**
 * DokuWiki Plugin authorstats (Action Component)
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

require_once DOKU_PLUGIN.'action.php';
require_once DOKU_PLUGIN.'authorstats/helpers.php';

class action_plugin_authorstats extends DokuWiki_Action_Plugin 
{

    var $supportedModes = array('xhtml', 'metadata');

    public function register(Doku_Event_Handler $controller) 
    {
        $controller->register_hook('ACTION_SHOW_REDIRECT', 'BEFORE', $this, '_updateSavedStats');
        $controller->register_hook('PARSER_CACHE_USE','BEFORE', $this, '_cachePrepare');
    }


    // Updates the saved statistics by checking the last lines
    // in the /data/meta/ directory
    public function _updateSavedStats() 
    {   
        global $conf;
        $dir = $conf['metadir'] . '/';

        // Return the files in the directory /data/meta
        $files = $this->_getChangeLogs($dir);

        // Read saved data from JSON file
        $sd = authorstatsReadJSON();

        // Get last change 
        $lastchange = empty($sd) ?  (-1*PHP_INT_MAX )-1 : (int) $sd["lastchange"];
        $newlast = $lastchange; 
        foreach ($files as $file) 
        {
            $file_contents = array_reverse(file($file));
            foreach($file_contents as $line)
            {
                $r = $this->_parseChange($line);
                if ($r["timestamp"] <= $lastchange)
                    break;

                // Update the last if there is a more recent change
                $newlast = max($newlast, $r["timestamp"]);

                // If the author is not in the array, initialize his stats
                if (!isset($sd["authors"][$r["author"]]))
                {
                    $sd["authors"][$r["author"]]["C"] = 0; 
                    $sd["authors"][$r["author"]]["E"] = 0;
                    $sd["authors"][$r["author"]]["e"] = 0;
                    $sd["authors"][$r["author"]]["D"] = 0;
                    $sd["authors"][$r["author"]]["R"] = 0;
                    $sd["authors"][$r["author"]]["pm"] = Array(); 
                } 
                else
                {
                    // Initialize month if doesn't exist
                    // else increment it
                    if (!isset($sd["authors"][$r["author"]]["pm"][$r["date"]])) 
                        $sd["authors"][$r["author"]]["pm"][$r["date"]] = 1;
                    else 
                        $sd["authors"][$r["author"]]["pm"][$r["date"]]++;
                }
                $sd["authors"][$r["author"]][$r["type"]]++; 
            }
        }
        $sd["lastchange"] = $newlast;
        authorstatsSaveJSON($sd);
    }

    // If the page is no more recent than the modification of the json file, refresh the page.
    public function _cachePrepare(&$event, $param) 
    {   
        $cache =& $event->data;

        if(!isset($cache->page)) return;
        if(!isset($cache->mode) || !in_array($cache->mode, $this->supportedModes)) return;

        $enabled = p_get_metadata($cache->page, 'authorstats-enabled');

        if (isset($enabled)) 
        {
            if (@filemtime($cache->cache) < @filemtime(DOKU_PLUGIN."authorstats/authorstats.json")) 
            {
                $event->preventDefault();
                $event->stopPropagation();
                $event->result = false;
            }
        }
    }

    function _getChangeLogs($dir, &$files = array()) 
    {    
        if ($dh = opendir($dir)) 
        {
            while (($res = readdir($dh)) !== false) 
            {
                if(is_dir($dir . $res . '/') && $res != '.' && $res != '..') array_merge($files, $this->_getChangeLogs($dir . $res . '/', $files));
                else 
                {
                    if (strpos($res, '.changes') !== false && $res[0] != '_') $files[] = $dir . $res; 
                }
            } 
            closedir($dh);
        }
        return $files; 
    } 

    function _parseChange($line)
    {
        $record = Array();
        $parts = explode(DOKU_TAB, $line);
        if ($parts && $parts[4] != "") 
        {
            $record["timestamp"] = $parts[0];
            $record["type"] = $parts[2];
            $record["author"] = $parts[4];
            $record["date"] = date("Ym",$parts[0]);
        }
        return $record;
    }
}
// vim:ts=4:sw=4:et:
