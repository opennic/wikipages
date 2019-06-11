<?php

/**
 * mock class to access the helper_plugin_move_plan::stepThroughDocuments function in tests
 */
class helper_plugin_move_plan_mock extends helper_plugin_move_plan {

    public $moveLog = array();

    public function __construct() {
        parent::__construct();
        $this->MoveOperator  = new helper_plugin_move_op_mock;
    }

    public function stepThroughDocumentsCall($type = parent::TYPE_PAGES, $skip = false) {
        return $this->stepThroughDocuments($type, $skip);
    }

    public function getMoveOperator() {
        return $this->MoveOperator;
    }

    public function setMoveOperator($newMoveOPerator) {
        $this->MoveOperator = $newMoveOPerator;
    }

    public function build_log_line($type, $from, $to, $success) {
        $logEntry = array($type,$from,$to,$success);
        array_push($this->moveLog,$logEntry);
        return parent::build_log_line($type, $from, $to, $success);
    }



}

class helper_plugin_move_op_mock extends helper_plugin_move_op {

    public $movedPages = array();
    public $fail = false;

    public function movePage($src, $dst) {
        if ($this->fail !== false && count($this->movedPages) == $this->fail) {
            $this->fail=false;
            // Store a msg as it is expected by the plugin
            msg("Intentional failure in test case.", -1);
            return false;
        }
        $moveOperation = array($src => $dst);
        array_push($this->movedPages,$moveOperation);
        return true;
    }
}




/**
 * Test cases for helper_plugin_move_plan::stepThroughDocuments function of the move plugin
 *
 * @group plugin_move
 * @group plugin_move_unittests
 * @group plugins
 * @group unittests
 */
class plugin_move_stepThroughDocuments_test extends DokuWikiTest {

    public function setUp() {
        parent::setUp();
        $opts_file = dirname(DOKU_CONF) . '/data/meta/__move_opts';
        if(file_exists($opts_file)){
            unlink($opts_file);
        }

        $file = "oldns:page01\tnewns:page01\n"
            . "oldns:page02\tnewns:page02\n"
            . "oldns:page03\tnewns:page03\n"
            . "oldns:page04\tnewns:page04\n"
            . "oldns:page05\tnewns:page05\n"
            . "oldns:page06\tnewns:page06\n"
            . "oldns:page07\tnewns:page07\n"
            . "oldns:page08\tnewns:page08\n"
            . "oldns:page09\tnewns:page09\n"
            . "oldns:page10\tnewns:page10\n"
            . "oldns:page11\tnewns:page11\n"
            . "oldns:page12\tnewns:page12\n"
            . "oldns:page13\tnewns:page13\n"
            . "oldns:page14\tnewns:page14\n"
            . "oldns:page15\tnewns:page15\n"
            . "oldns:page16\tnewns:page16\n"
            . "oldns:page17\tnewns:page17\n"
            . "oldns:page18\tnewns:page18";
        $file_path = dirname(DOKU_CONF) . '/data/meta/__move_pagelist';
        io_saveFile($file_path,$file);
    }


    /**
     * @covers helper_plugin_move_plan::stepThroughDocuments
     */
    public function test_stepThroughPages() {

        $file_path = dirname(DOKU_CONF) . '/data/meta/__move_pagelist';
        $mock = new helper_plugin_move_plan_mock();
        $actual_return = $mock->stepThroughDocumentsCall();
        $actual_file = file_get_contents($file_path);
        $expected_file = "oldns:page01\tnewns:page01\n"
            . "oldns:page02\tnewns:page02\n"
            . "oldns:page03\tnewns:page03\n"
            . "oldns:page04\tnewns:page04\n"
            . "oldns:page05\tnewns:page05\n"
            . "oldns:page06\tnewns:page06\n"
            . "oldns:page07\tnewns:page07\n"
            . "oldns:page08\tnewns:page08";

        $expected_pages_run = -10;
        $this->assertSame($expected_pages_run,$actual_return,"return values differ");
        $this->assertSame($expected_file,$actual_file, "files differ");
        $actual_move_Operator = $mock->getMoveOperator();
        $this->assertSame(array('oldns:page18' => 'newns:page18',),$actual_move_Operator->movedPages[0]);
        $this->assertSame(array('oldns:page09' => 'newns:page09',),$actual_move_Operator->movedPages[9]);
        $this->assertTrue(!isset($actual_move_Operator->movedPages[10]));

        $expected_log = array('P','oldns:page18','newns:page18',true);
        $this->assertSame($expected_log,$mock->moveLog[0]);

        $expected_log = array('P','oldns:page09','newns:page09',true);
        $this->assertSame($expected_log,$mock->moveLog[9]);
        $this->assertTrue(!isset($mock->moveLog[10]));

        $opts_file = dirname(DOKU_CONF) . '/data/meta/__move_opts';
        $actual_options = unserialize(io_readFile($opts_file));
        $this->assertSame($expected_pages_run,$actual_options['pages_run'],'saved options are wrong');
    }

    /**
     * @covers helper_plugin_move_plan::stepThroughDocuments
     */
    public function test_stepThroughPages_skip() {

        $file_path = dirname(DOKU_CONF) . '/data/meta/__move_pagelist';
        $mock = new helper_plugin_move_plan_mock();
        $actual_return = $mock->stepThroughDocumentsCall(1,true);
        $actual_file = file_get_contents($file_path);
        $expected_file = "oldns:page01\tnewns:page01\n"
            . "oldns:page02\tnewns:page02\n"
            . "oldns:page03\tnewns:page03\n"
            . "oldns:page04\tnewns:page04\n"
            . "oldns:page05\tnewns:page05\n"
            . "oldns:page06\tnewns:page06\n"
            . "oldns:page07\tnewns:page07\n"
            . "oldns:page08\tnewns:page08";
        $expected_pages_run = -10;
        $this->assertSame($expected_pages_run,$actual_return,"return values differ");
        $this->assertSame($expected_file,$actual_file, "files differ");
        $actual_move_Operator = $mock->getMoveOperator();
        $this->assertSame(array('oldns:page17' => 'newns:page17',),$actual_move_Operator->movedPages[0]);
        $this->assertSame(array('oldns:page09' => 'newns:page09',),$actual_move_Operator->movedPages[8]);
        $this->assertTrue(!isset($actual_move_Operator->movedPages[9]));

        $expected_log = array('P','oldns:page17','newns:page17',true);
        $this->assertSame($expected_log,$mock->moveLog[0]);

        $expected_log = array('P','oldns:page09','newns:page09',true);
        $this->assertSame($expected_log,$mock->moveLog[8]);
        $this->assertTrue(!isset($mock->moveLog[9]));

        $opts_file = dirname(DOKU_CONF) . '/data/meta/__move_opts';
        $actual_options = unserialize(io_readFile($opts_file));
        $this->assertSame($expected_pages_run,$actual_options['pages_run'],'saved options are wrong');
    }

    /**
     * @covers helper_plugin_move_plan::stepThroughDocuments
     */
    public function test_stepThroughPages_fail() {

        $file_path = dirname(DOKU_CONF) . '/data/meta/__move_pagelist';
        $mock = new helper_plugin_move_plan_mock();
        $fail_at_item = 5;
        $actual_move_Operator = $mock->getMoveOperator();
        $actual_move_Operator->fail = $fail_at_item;
        $mock->setMoveOperator($actual_move_Operator);
        $actual_return = $mock->stepThroughDocumentsCall();
        $actual_file = file_get_contents($file_path);
        $expected_file = "oldns:page01\tnewns:page01\n"
            . "oldns:page02\tnewns:page02\n"
            . "oldns:page03\tnewns:page03\n"
            . "oldns:page04\tnewns:page04\n"
            . "oldns:page05\tnewns:page05\n"
            . "oldns:page06\tnewns:page06\n"
            . "oldns:page07\tnewns:page07\n"
            . "oldns:page08\tnewns:page08\n"
            . "oldns:page09\tnewns:page09\n"
            . "oldns:page10\tnewns:page10\n"
            . "oldns:page11\tnewns:page11\n"
            . "oldns:page12\tnewns:page12\n"
            . "oldns:page13\tnewns:page13";

        $expected_pages_run = false;
        $this->assertSame($expected_pages_run,$actual_return,"return values differ");
        $this->assertSame($expected_file,$actual_file, "files differ");
        $actual_move_Operator = $mock->getMoveOperator();
        $this->assertSame(array('oldns:page18' => 'newns:page18',),$actual_move_Operator->movedPages[0]);
        $lastIndex = 4;
        $this->assertSame(array('oldns:page14' => 'newns:page14',),$actual_move_Operator->movedPages[$lastIndex]);
        $this->assertTrue(!isset($actual_move_Operator->movedPages[$lastIndex + 1]));

        $expected_log = array('P','oldns:page13','newns:page13',false);
        $this->assertSame($expected_log,$mock->moveLog[5]);
        $this->assertTrue(!isset($mock->moveLog[6]));

        $opts_file = dirname(DOKU_CONF) . '/data/meta/__move_opts';
        $actual_options = unserialize(io_readFile($opts_file));
        $this->assertSame(-$fail_at_item,$actual_options['pages_run'],'saved options are wrong');
    }


    /**
     * @covers helper_plugin_move_plan::stepThroughDocuments
     */
    public function test_stepThroughPages_fail_autoskip() {
        global $conf;
        $conf['plugin']['move']['autoskip'] = '1';

        $file_path = dirname(DOKU_CONF) . '/data/meta/__move_pagelist';
        $mock = new helper_plugin_move_plan_mock();
        $actual_move_Operator = $mock->getMoveOperator();
        $actual_move_Operator->fail = 5;
        $mock->setMoveOperator($actual_move_Operator);
        $actual_return = $mock->stepThroughDocumentsCall();

        $expected_pages_run = -10;
        $this->assertSame($expected_pages_run,$actual_return,"return values differ");

        $actual_file = file_get_contents($file_path);
        $expected_file = "oldns:page01\tnewns:page01\n"
            . "oldns:page02\tnewns:page02\n"
            . "oldns:page03\tnewns:page03\n"
            . "oldns:page04\tnewns:page04\n"
            . "oldns:page05\tnewns:page05\n"
            . "oldns:page06\tnewns:page06\n"
            . "oldns:page07\tnewns:page07\n"
            . "oldns:page08\tnewns:page08";

        $this->assertSame($expected_file,$actual_file, "files differ");


        $actual_move_Operator = $mock->getMoveOperator();
        $this->assertSame(array('oldns:page18' => 'newns:page18',),$actual_move_Operator->movedPages[0]);

        $lastIndex = 8;
        $this->assertSame(array('oldns:page09' => 'newns:page09',),$actual_move_Operator->movedPages[$lastIndex]);
        $this->assertTrue(!isset($actual_move_Operator->movedPages[$lastIndex + 1]), "The number of moved pages is incorrect");


        $expected_log = array('P','oldns:page18','newns:page18',true);
        $this->assertSame($expected_log,$mock->moveLog[0]);

        $expected_log = array('P','oldns:page13','newns:page13',false);
        $this->assertSame($expected_log,$mock->moveLog[5]);

        $expected_log = array('P','oldns:page09','newns:page09',true);
        $this->assertSame($expected_log,$mock->moveLog[9]);

        $this->assertTrue(!isset($mock->moveLog[10]), "The number of logged items is incorrect");

        $opts_file = dirname(DOKU_CONF) . '/data/meta/__move_opts';
        $actual_options = unserialize(io_readFile($opts_file));
        $this->assertSame($expected_pages_run,$actual_options['pages_run'],'saved options are wrong');
    }


}
