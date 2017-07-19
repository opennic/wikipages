<?php

/**
 * Instruction re-writer
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Mykola Ostrovskyy <spambox03@mail.ru>
 */

if (!class_exists('instruction_rewriter', false)) {

class instruction_rewriter {

    private $correction;

    /**
     * Constructor
     */
    public function __construct() {
        $this->correction = array();
    }

    /**
     *
     */
    public function addCorrections($correction) {
        foreach ($correction as $c) {
            $this->correction[$c->getIndex()][] = $c;
        }
    }

    /**
     *
     */
    public function process(&$instruction) {
        if (count($this->correction) > 0) {
            $index = $this->getCorrectionIndex();
            $corrections = count($index);
            $instructions = count($instruction);
            $output = array();
            for ($c = 0, $i = 0; $c < $corrections; $c++, $i++) {
                /* Copy all instructions that are before the next correction */
                for ( ; $i < $index[$c]; $i++) {
                    $output[] = $instruction[$i];
                }
                /* Apply the corrections */
                $preventDefault = false;
                foreach ($this->correction[$i] as $correction) {
                    $preventDefault = ($preventDefault || $correction->apply($instruction, $output));
                }
                if (!$preventDefault) {
                    $output[] = $instruction[$i];
                }
            }
            /* Copy the rest of instructions after the last correction */
            for ( ; $i < $instructions; $i++) {
                $output[] = $instruction[$i];
            }
            /* Handle appends */
            if (array_key_exists(-1, $this->correction)) {
                $this->correction[-1]->apply($instruction, $output);
            }
            $instruction = $output;
        }
    }

    /**
     *
     */
    private function getCorrectionIndex() {
        $result = array_keys($this->correction);
        asort($result);
        /* Remove appends */
        if (reset($result) == -1) {
            unset($result[key($result)]);
        }
        return array_values($result);
    }
}

class instruction_rewriter_correction {

    private $index;

    /**
     * Constructor
     */
    public function __construct($index) {
        $this->index = $index;
    }

    /**
     *
     */
    public function getIndex() {
        return $this->index;
    }
}

class instruction_rewriter_delete extends instruction_rewriter_correction {

    /**
     * Constructor
     */
    public function __construct($index) {
        parent::__construct($index);
    }

    /**
     *
     */
    public function apply($input, &$output) {
        return true;
    }
}

class instruction_rewriter_call_list extends instruction_rewriter_correction {

    private $call;

    /**
     * Constructor
     */
    public function __construct($index) {
        parent::__construct($index);
        $this->call = array();
    }

    /**
     *
     */
    public function addCall($name, $data) {
        $this->call[] = array($name, $data);
    }

    /**
     *
     */
    public function addPluginCall($name, $data, $state, $text = '') {
        $this->call[] = array('plugin', array($name, $data, $state, $text));
    }

    /**
     *
     */
    public function appendCalls(&$output, $position) {
        foreach ($this->call as $call) {
            $output[] = array($call[0], $call[1], $position);
        }
    }
}

class instruction_rewriter_insert extends instruction_rewriter_call_list {

    /**
     * Constructor
     */
    public function __construct($index) {
        parent::__construct($index);
    }

    /**
     *
     */
    public function apply($input, &$output) {
        $this->appendCalls($output, $input[$this->index][2]);
        return false;
    }
}

class instruction_rewriter_append extends instruction_rewriter_call_list {

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct(-1);
    }

    /**
     *
     */
    public function apply($input, &$output) {
        $lastCall = end($output);
        $this->appendCalls($output, $lastCall[2]);
        return false;
    }
}

}
