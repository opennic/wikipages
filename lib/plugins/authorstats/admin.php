<?php

if (!defined("DOKU_INC")) die();
if (!defined("DOKU_PLUGIN")) define("DOKU_PLUGIN", DOKU_INC . "lib/plugins/");

class admin_plugin_authorstats extends DokuWiki_Admin_Plugin
{
    public function html()
    {
        if (isset($_REQUEST["init_db"])) {
            $action = plugin_load("action", "authorstats");
            $action->_initializeData();
        }
        echo "<h1>Authorstats</h1>";
        $action = script();
        echo "<form action='" . $action . "' method='post' id='plugin__upgrade_form'>";
        echo "<input type='hidden' name='do' value='admin' />";
        echo "<input type='hidden' name='page' value='authorstats' />";
        echo "<input type='hidden' name='sectok' value='" . getSecurityToken() . "' />";
        echo "<div>" . $this->getLang("admin-description") . "</div>";
        echo "<button type='submit' name='init_db' value='1'>" . $this->getLang("admin-button") . "</button>";
        echo "</form>";
    }
}
