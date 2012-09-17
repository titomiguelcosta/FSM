<?php

require_once __DIR__ . '/../vendor/autoload.php';

use TitoMiguelCosta\FSM\FSM;

class FSMTest extends PHPUnit_Framework_TestCase
{

    /**
     * FSM to parse an expression, uses an object as the payload
     * A valid expression is of type ([0-2], [0-2])
     */
    public function testPosition()
    {
        $fsm = new FSM('START', new SplQueue());

        $fsm->addTransition('(', 'START', 'BEFORE_X', null);
        $fsm->addTransitions(range(0, 2), 'BEFORE_X', 'BEFORE_Y', array($this, 'doEnqueue'));
        $fsm->addTransitions(array(',', ' '), 'BEFORE_Y', 'BEFORE_Y', null);
        $fsm->addTransitions(range(0, 2), 'BEFORE_Y', 'Y', array($this, 'doEnqueue'));
        $fsm->addTransition(')', 'Y', 'END', null);

        $input = '(2, 1)';

        $fsm->processString($input);

        $this->assertEquals($fsm->getPayload()->count(), 2, 'Queue has two elements');
        $this->assertEquals($fsm->getPayload()->dequeue(), 2, 'First element has value 2');
        $this->assertEquals($fsm->getPayload()->dequeue(), 1, 'Last element has value 1');
        $this->assertTrue($fsm->getPayload()->isEmpty(), 'Queue is empty');
    }

    public function doEnqueue($symbol, SplQueue $queue)
    {
        $queue->enqueue($symbol);
    }

    /**
     * FSM to parse an expression in the Reverse Polish Notation
     * uses an array as the payload
     */
    public function testRPN()
    {
        $stack = array();

        $fsm = new FSM('INIT', $stack);
        $fsm->setDefaultTransition('INIT', 'Error');

        $fsm->addTransitionAny('INIT', 'INIT');
        $fsm->addTransition('=', 'INIT', 'INIT', array($this, 'doEqual'));
        $fsm->addTransitions(range(0, 9), 'INIT', 'BUILD_NUMBER', array($this, 'doBeginBuildNumber'));
        $fsm->addTransitions(range(0, 9), 'BUILD_NUMBER', 'BUILD_NUMBER', array($this, 'doBuildNumber'));
        $fsm->addTransition(' ', 'BUILD_NUMBER', 'INIT', array($this, 'doEndBuildNumber'));
        $fsm->addTransitions(array('+', '-', '*', '/'), 'INIT', 'INIT', array($this, 'doOperator'));

        $input = '1 9 + 29 7 * * =';

        $fsm->processString($input);
    }

    public function doEqual($symbol, &$stack)
    {
        $this->assertEquals(array_pop($stack), 2030);
    }
    public function doBuildNumber($symbol, &$stack)
    {
        $number = array_pop($stack);
        array_push($stack, $number.$symbol);
    }
    public function doBeginBuildNumber($symbol, &$stack)
    {
        array_push($stack, $symbol);
    }
    public function doEndBuildNumber($symbol, &$stack)
    {
        $number = array_pop($stack);
        array_push($stack, (int) $number);
    }
    public function doOperator($symbol, &$stack)
    {
        $a = array_pop($stack);
        $b = array_pop($stack);

        switch ($symbol)
        {
            case '+':
                $number = $a + $b;
                break;
            case '-':
                $number = $a - $b;
                break;
            case '*':
                $number = $a * $b;
                break;
            case '/':
                $number = $a / $b;
                break;
        }

        array_push($stack, $number);
    }
}