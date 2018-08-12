<?php
if(!defined("DOKU_INC")) define("DOKU_INC",realpath(dirname(__FILE__)."/../../")."/");
if(!defined("DOKU_PLUGIN")) define("DOKU_PLUGIN",DOKU_INC."lib/plugins/");
require_once(DOKU_PLUGIN."syntax.php");
class syntax_plugin_devote extends DokuWiki_Syntax_Plugin {
	public function getInfo() {
		return array(
			"author" => "Katie Holly",
			"email"  => "fusl@meo.ws",
			"date"   => "2017/10/06",
			"name"   => "OpenNIC Democracy Vote Plugin",
			"desc"   => "",
			"url"    => "https://opennic.org/",
		);
	}
	public function getType()  { return "substition";}
	public function getPType() { return "block";}
	public function getSort()  { return 168; }
	public function connectTo($mode) {
		$this->Lexer->addSpecialPattern("<devote\b.*?>.+?</devote>", $mode, "plugin_devote");
	}
	public function handle($match, $state, $pos, Doku_Handler $handler) {
		$match = substr($match, 8, -9);
		list($parameterStr, $choiceStr) = preg_split("/>/u", $match, 2);
		preg_match_all("/(\w+?)=\"(.*?)\"/", $parameterStr, $regexMatches, PREG_SET_ORDER);
		$title = "";
		$closed = false;
		for ($i = 0; $i < sizeof($regexMatches); $i++) {
			$name  = strtoupper($regexMatches[$i][1]);
			$value = $regexMatches[$i][2];
			if ($name === "TITLE") {
				$title = trim($value);
			} elseif ($name === "CLOSE" && ($timestamp = strtotime($value)) !== false && time() > $timestamp) {
				$closed = true;
			}
		}
		$choices = array();
		preg_match_all("/^\s{0,3}\* (.*?)$/m", $choiceStr, $matches, PREG_PATTERN_ORDER);
		foreach ($matches[1] as $choice) {
			$choice = preg_replace("#\\\\\\\\#", "<br />", $choice);         // two(!) backslashes for a newline
			$choice = preg_replace("#\*\*(.*?)\*\*#", "<b>\1</b>", $choice); // bold
			$choice = preg_replace("#__(.*?)__#", "<u>\1</u>", $choice);     // underscore
			$choice = preg_replace("#//(.*?)//#", "<i>\1</i>", $choice);     // italic
			$choice = trim($choice);
			if (!empty($choice)) {
				array_push($choices, $choice);
			}
		}
		return array(
			"choices" => $choices,
			"title" => $title,
			"closed" => $closed
		);
	}
	private function resubmit_form($renderer, $formid, $selection, $cast_vote, $resubmit_timer) {
		$doc = '';
		$doc .= '<div id="dw__msgarea" class="small"><div class="alert alert-warning">Please wait, your vote is being casted. Please do NOT close or leave this site while this is in progress.</div></div>';
		$doc .= '<form name="devote_resubmit" action="" method="post" accept-charset="utf-8" >';
		$doc .= '<input type="hidden" name="devote_formid" value="' . hsc($formid) . '">';
		$doc .= '<input type="hidden" name="devote_selection" value="' . hsc($selection) . '">';
		$doc .= '<input type="hidden" name="devote_cast_vote" value="' . hsc($cast_vote) . '">';
		$doc .= '<input type="hidden" name="devote_resubmit_timer" value="' . hsc($resubmit_timer + 1) . '">';
		$doc .= '</form>';
		$doc .= '<script type="text/javascript">setTimeout(function () { document.devote_resubmit.submit() }, ' . $resubmit_timer . ' * 1000);</script>';
		$renderer->doc = $doc . $renderer->doc;
	}
	public function render($mode, Doku_Renderer $renderer, $data) {
		if ($mode != "xhtml" || !sizeof($data["choices"])) return false;
		$renderer->info["cache"] = false;
		//global $conf;
		global $INFO;
		global $ACT;
		global $REV;
		$choices = $data["choices"];
		$title = $data["title"];
		$closed = $data["closed"];
		$votehash = md5("devote_" . $title);
		$votes = array();
		$filename = metaFN($votehash, ".devote");
		if (file_exists($filename)) {
			$votes = json_decode(file_get_contents($filename), true);
		}
		if (!$closed && isset($INFO["userinfo"]) && $ACT === "show" && $_REQUEST["devote_formid"] === $votehash && $REV === 0 && !empty($_REQUEST["devote_cast_vote"]) && in_array($_REQUEST["devote_selection"], $choices)) {
			$fp = fopen($filename, "r+");
			if (!flock($fp, LOCK_EX | LOCK_NB, $flock_locked) || $flock_locked) {
				return $this->resubmit_form($renderer, $_REQUEST["devote_formid"], $_REQUEST["devote_selection"], $_REQUEST["devote_cast_vote"], is_numeric($_REQUEST["devote_resubmit_timer"]) ? $_REQUEST["devote_resubmit_timer"] : 0);
			}
			$votes[$INFO["client"]] = array(
				"c" => $_REQUEST["devote_selection"],
				"t" => time()
			);
			ksort($votes, SORT_STRING | SORT_FLAG_CASE);
			file_put_contents($filename, json_encode($votes, JSON_PRETTY_PRINT));
			fclose($fp);
			$renderer->doc = '<div id="dw__msgarea" class="small"><div class="alert alert-success">Your vote was successfully casted.</div></div>' . $renderer->doc;
		}
		$votestats = array();
		$votetotal = 0;
		foreach ($choices as $choice) {
			$votestats[$choice] = 0;
		}
		foreach ($votes as $voteuser => $votedata) {
			if (isset($votestats[$votedata["c"]])) {
				$votestats[$votedata["c"]]++;
				$votetotal++;
			}
		}
		// TODO: Clean up this PHP fuckery
		// TODO: Send JSON to browser and let it parse the data into a table instead of us generating the HTML
		// Note: I purposefully used single-quotes instead of double quotes here to make the HTML just a *LITTLE* bit more readable
		$renderer->doc .= '<div class="devote_scrollcontainer">';
		$renderer->doc .= '<form action="" method="post" accept-charset="utf-8" >';
		$renderer->doc .= '<input type="hidden" name="devote_formid" value="' . $votehash . '">';
		$renderer->doc .= '<table class="inline table table-striped table-condensed">';
		$renderer->doc .= '<tbody>';
		$renderer->doc .= '<tr>';
		$renderer->doc .= '<th class="centeralign" colspan="' . (sizeof($choices) + 1) . '">' . hsc($title) . '</th>';
		$renderer->doc .= '</tr>';
		$renderer->doc .= '<tr>';
		$renderer->doc .= '<td></td>';
		foreach ($choices as $choice) {
			$renderer->doc .= '<th class="centeralign">' . hsc($choice) . '</th>';
		}
		$renderer->doc .= '</tr>';
		if (!$closed && isset($INFO["userinfo"]) && $ACT === "show" && $REV === 0) {
			$renderer->doc .= '<tr>';
			$renderer->doc .= '<th class="rightalign"><input type="submit" value="Your vote:" name="devote_cast_vote" class="btn btn-default btn-xs"></th>';
			foreach ($choices as $choice) {
				$checked = "";
				if (isset($votes[$INFO["client"]]) && $votes[$INFO["client"]]["c"] === $choice) {
					$checked = ' checked="checked"';
				}
				$renderer->doc .= '<td class="centeralign"><input type="radio" name="devote_selection" value="' . hsc($choice) . '"' . $checked . '></td>';
			}
			$renderer->doc .= '</tr>';
		}
		$renderer->doc .= '<tr>';
		$renderer->doc .= '<th class="rightalign">Result:</th>';
		if (!$votetotal) {
			$renderer->doc  .= '<td class="centeralign" colspan="' . sizeof($choices) . '">No votes</td>';
		} else {
			foreach ($choices as $choice) {
				$renderer->doc .= '<td class="centeralign">' . $votestats[$choice] . ' (' . ($votetotal ? round($votestats[$choice] / $votetotal * 100, 1) : 0) . '%)</td>';
			}
		}
		$renderer->doc .= '</tr>';
		foreach ($votes as $voteuser => $votedata) {
			$renderer->doc .= '<tr>';
			$renderer->doc .= '<td class="rightalign">' . $voteuser . '</td>';
			foreach ($choices as $choice) {
				if ($choice === $votedata["c"]) {
					$renderer->doc .= '<td class="centeralign" style="background-color: #afa;"><img src="' . DOKU_BASE . 'lib/images/success.png" alt="X" title="' . strftime($conf["dformat"], $votedata["t"]) . '"></td>';
				} else {
					$renderer->doc .= '<td class="centeralign" style="background-color: #faa;"></td>';
				}
			}
			$renderer->doc .= '</tr>';
		}
		$renderer->doc .= '</table>';
		$renderer->doc .= '</form>';
		$renderer->doc .= '</div>';
	}
}
?>
