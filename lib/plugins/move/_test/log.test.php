<?php

/**
 * Test cases log functionality of the move plugin
 *
 * @group plugin_move
 * @group plugin_move_unittests
 * @group plugins
 * @group unittests
 */
class plugin_move_log_test extends DokuWikiTest {

    protected $pluginsEnabled = array('move',);

    public function test_log_one_line_success() {
        /** @var helper_plugin_move_plan $plan */
        $plan = plugin_load('helper', 'move_plan');
        $now = time();
        $date = date('Y-m-d H:i:s', $now);

        $actual_log = $plan->build_log_line('P','oldpage','newpage',true);

        $expected_log = "$now\t$date\tP\toldpage\tnewpage\tsuccess\t\n";

        $this->assertSame($expected_log, $actual_log);
    }

    public function test_log_build_line_failure() {
        global $MSG;
        $MSG = array();
        $msg = array('msg'=>"TestMessage01",);
        array_push($MSG,$msg);

        /** @var helper_plugin_move_plan $plan */
        $plan = plugin_load('helper', 'move_plan');
        $now = time();
        $date = date('Y-m-d H:i:s', $now);

        $actual_log = $plan->build_log_line('P','oldpage','newpage',false);

        $expected_log = "$now\t$date\tP\toldpage\tnewpage\tfailed\tTestMessage01\n";

        $this->assertSame($expected_log, $actual_log);
    }

}
