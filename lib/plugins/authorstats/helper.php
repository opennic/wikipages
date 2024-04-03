<?php

/**
 * DokuWiki Plugin authorstats (Helper Functions)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  George Chatzisofroniou <sophron@latthi.com>
 * @author  Constantinos Xanthopoulos <conx@xanthopoulos.info>
 */

class helper_plugin_authorstats extends DokuWiki_Plugin
{

    var $basedir;
    var $summaryfile;

    function __construct()
    {
        global $conf;
        $this->basedir = $conf['cachedir'] . '/_authorstats';
        $this->summaryfile = $this->basedir . "/summary.json";
    }

    // Creat directory if missing
    public function createDirIfMissing()
    {
        if (!file_exists($this->basedir)) {
            mkdir($this->basedir, 0755);
        }
    }

    public function statsFileExists()
    {
        return file_exists($this->basedir) ? true : false;
    }

    // Read the saved statistics from the JSON file
    public function readJSON()
    {
        $file = @file_get_contents($this->summaryfile);
        if (!$file) return array();
        return json_decode($file, true);
    }

    // Save the statistics into the JSON file
    public function saveJSON($authors)
    {
        $this->createDirIfMissing();
        $json = json_encode($authors, true);
        file_put_contents($this->summaryfile, $json);
    }

    // Read the saved statistics for user from the JSON file
    public function readUserJSON($loginname)
    {
        $file_contents = @file_get_contents($this->basedir . "/" . $loginname . ".json");
        if (!$file_contents) return array();
        return json_decode($file_contents, true);
    }

    // Save the statistics of user into the JSON file
    public function saveUserJSON($loginname, $pages)
    {
        $this->createDirIfMissing();
        $json = json_encode($pages, true);
        file_put_contents($this->basedir . "/" . $loginname . ".json", $json);
    }

    # Recursive version of glob
    # Source: https://stackoverflow.com/questions/17160696/php-glob-scan-in-subfolders-for-a-file
    public function rglob($pattern, $flags = 0)
    {
        $files = glob($pattern, $flags);
        foreach (glob(dirname($pattern) . "/*", GLOB_ONLYDIR | GLOB_NOSORT) as $dir) {
            $files = array_merge(
                [],
                ...[$files, $this->rglob($dir . "/" . basename($pattern), $flags)]
            );
        }
        return $files;
    }
}
