<?php

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

/**
 * Test cases for the move plugin
 *
 * @group plugin_move
 * @group plugins
 */
class plugin_move_cache_handling_test extends DokuWikiTest {

    function setUp(): void {
        parent::setUpBeforeClass();
        $this->pluginsEnabled[] = 'move';
        parent::setUp();
    }

    /**
     * @group slow
     */
    function test_cache_handling() {
        $testid = 'wiki:bar:test';
        saveWikiText($testid,
            '[[wiki:foo:]]', 'Test setup');
        idx_addPage($testid);
        saveWikiText('wiki:foo:start',
            'bar', 'Test setup');
        idx_addPage('wiki:foo:start');

        sleep(1); // wait in order to make sure that conditions with < give the right result.
        p_wiki_xhtml($testid); // populate cache

        $cache = new cache_renderer($testid, wikiFN($testid), 'xhtml');
        $this->assertTrue($cache->useCache());

        /** @var helper_plugin_move_op $move */
        $move = plugin_load('helper', 'move_op');
        $this->assertTrue($move->movePage('wiki:foo:start', 'wiki:foo2:start'));

        $cache = new cache_renderer($testid, wikiFN($testid), 'xhtml');
        $this->assertFalse($cache->useCache());

    }

}
