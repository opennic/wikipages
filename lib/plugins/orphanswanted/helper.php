<?php
/**
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     <dae@douglasedmunds.com>
 * @author     Andy Webber <dokuwiki at andywebber dot com>
 * @author     Federico Ariel Castagnini
 * @author     Cyrille37 <cyrille37@gmail.com>
 * @author     Matthias Schulte <dokuwiki@lupo49.de>
 * @author     Rik Blok <rik dot blok at ubc dot ca>
 * @author     Christian Paul <christian at chrpaul dot de>
 * @author     alexdraconian
 */
// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');

require_once(DOKU_INC.'inc/search.php');

class helper_plugin_orphanswanted extends DokuWiki_Plugin {

    //    three choices
    //    $params_array used to extract excluded namespaces for report
    //    orphans =  orph_report_table($data, true, false, $params_array);
    //    wanted =  orph_report_table($data, false, true), $params_array;
    //    valid  =  orph_report_table($data, true, true, $params_array);

    /**
     * Find all page list with wiki's internal indexer.
     */
    function _get_page_data() {
        $all_pages = idx_get_indexer()->getPages();
        $pages = array();
        foreach($all_pages as $pageid) {
            $pages[$pageid] = array("exists"=>page_exists($pageid), "links"=>0);
        }

        foreach($all_pages as $pageid) {

            if (!page_exists($pageid)) continue;

            $relation_data = p_get_metadata($pageid, 'relation references', METADATA_DONT_RENDER);
            if (!is_null($relation_data)) {
                foreach($relation_data as $name => $exists) {
                    $pages[$name]['exists'] = $exists;
                    $pages[$name]['links'] = isset($pages[$name]['links']) ? $pages[$name]['links'] + 1 : 1;
                }
            }
        }

        return $pages;
    }

    function orphan_pages($params_array) {
        $data = $this->_get_page_data();

        $result = '';
        $result .=  $this->orph_report_table($data, true, false, $params_array, 'orphan');

        return $result;
    }

    function wanted_pages($params_array) {
        $data = $this->_get_page_data();

        $result = '';
        $result .=  $this->orph_report_table($data, false, true, $params_array, 'wanted');

        return $result;
    }

    function valid_pages($params_array) {
        $data = $this->_get_page_data();

        $result = '';
        $result .=  $this->orph_report_table($data, true, true, $params_array, 'valid');

        return $result;
    }

    function all_pages($params_array) {
        $data = $this->_get_page_data();

        $result = '';
        $result .= "</p><p>Orphans</p><p>";
        $result .= $this->orph_report_table($data, true, false, $params_array, 'orphan');
        $result .= "</p><p>Wanted</p><p>";
        $result .= $this->orph_report_table($data, false, true, $params_array, 'wanted');
        $result .= "</p><p>Valid</p><p>";
        $result .= $this->orph_report_table($data, true, true, $params_array, 'valid');

        return $result;
    }

    function orph_report_table($data, $page_exists, $has_links, $params_array, $caller = null) {
        global $conf;
        $ignoredPages = $this->getConf('ignoredpages'); // Fetch pages which shouldn't be listed
        if($ignoredPages != '') {
            $ignoredPages = explode(';', $ignoredPages);
        } else {
            $ignoredPages = null;
        }

        $show_heading = ($page_exists && $conf['useheading']) ? true : false ;
        //take off $params_array[0];
        $include_array = $params_array[1];
        $exclude_array = $params_array[2];

        $count = 1;
        $output = '';

        // for valid html - need to close the <p> that is feed before this
        $output .= '</p>';
        $output .= '<table class="inline"><tr><th> # </th><th> ID </th>'
                    . ($show_heading ? '<th>Title</th>' : '' )
                    . ($caller != "orphan" ? '<th>Links</th>' : '')
                    . '</tr>'
                    . "\n" ;

        // Sort by namespace and name
        ksort($data);

        // Sort descending by existing links.
        // This does not make sense for orphans since they don't have links.
        if ($caller != "orphan") {
            arsort($data);
        }

        foreach($data as $id=>$item) {

            if( ! ((array_key_exists('exists', $item)) and ($item['exists'] == $page_exists) and (array_key_exists('links', $item)) and (($item['links'] <> 0)== $has_links)) ) continue ;

            // $id is a string, looks like this: page, namespace:page, or namespace:<subspaces>:page
            $match_array = explode(":", $id);
            //remove last item in array, the page identifier
            $match_array = array_slice($match_array, 0, -1);
            //put it back together
            $page_namespace = implode (":", $match_array);
            //add a trailing :
            $page_namespace = $page_namespace . ':';

            if (empty($include_array)) {
                // if inclusion list is empty then show all namespaces
                $show_it = true;
            } else {
                // otherwise only show if in inclusion list
                $show_it = false;
                foreach ($include_array as $include_item) {
                    //add a trailing : to each $item too
                    $include_item = $include_item . ":";
                    // need === to avoid boolean false
                    // strpos(haystack, needle)
                    // if exclusion is beginning of page's namespace, block it
                    if (strpos($page_namespace, $include_item) === 0) {
                        //there is a match, so show it and move on
                        $show_it = true;
                        break;
                    }
                }
            }

            if(!is_null($ignoredPages) && in_array($id, $ignoredPages)) {
                if ($conf['allowdebug']) echo "Skipped page (global ignored): " . $id . "<br />";
                $show_it = false;
            } elseif(isHiddenPage($id)) {
                if ($conf['allowdebug']) echo "Skipped page (global hidden): " . $id . "<br />";
                $show_it = false;
            } elseif ( $show_it )  {
                //check if blocked by exclusion list
                foreach ($exclude_array as $exclude_item) {
                    //add a trailing : to each $item too
                    $exclude_item = $exclude_item . ":";
                    // need === to avoid boolean false
                    // strpos(haystack, needle)
                    // if exclusion is beginning of page's namespace , block it
                    if (strpos($page_namespace, $exclude_item) === 0) {
                        //there is a match, so block it and move on
                        $show_it = false;
                        break;
                    }
                }
            }

            if($show_it) {
                $output .=  "<tr><td>$count</td><td><a href=\"". wl($id)
                            . "\" class=\"" . ($page_exists ? "wikilink1" : "wikilink2") . "\" >"
                            . $id .'</a></td>'
                            . ($show_heading ? '<td>' . hsc(p_get_first_heading($id)) .'</td>' : '' );

                if($caller != "orphan") { // Skip "link" column if user wants orphan pages only
                    $output .= '<td>' . $item['links']
                                . ($has_links ? "&nbsp;:&nbsp;<a href=\"". wl($id, 'do=backlink')
                                . "\" class=\"wikilink1\">Show&nbsp;backlinks</a>" : '') . "</td>";
                }
                $output .= "</tr>\n";
                $count++;
            }
        }

        $output .=  "</table>\n";
        //for valid html = need to reopen a <p>
        $output .= '<p>';

        return $output;
    }
}
