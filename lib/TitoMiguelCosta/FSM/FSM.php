<?php

/* vim: set expandtab softtabstop=4 tabstop=4 shiftwidth=4: */
/**
 * Copyright (c) 2002-2011 Jon Parise <jon@php.net>
 * Copyright (c) 2012 Tito Miguel Costa <fsm@titomiguelcosta.com>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to
 * deal in the Software without restriction, including without limitation the
 * rights to use, copy, modify, merge, publish, distribute, sublicense, and/or
 * sell copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * $Id: FSM.php 308377 2011-02-16 04:51:20Z jon $
 *
 * @package FSM
 */

namespace TitoMiguelCosta\FSM;

/**
 * This class implements a Finite State Machine (FSM).
 *
 * In addition to maintaining state, this FSM also maintains a user-defined
 * payload, therefore effectively making the machine a Push-Down Automata
 * (a finite state machine with memory).
 *
 * This code is based on Noah Spurrier's Finite State Machine (FSM) submission
 * to the Python Cookbook:
 *
 *      http://aspn.activestate.com/ASPN/Cookbook/Python/Recipe/146262
 *
 * @author  Jon Parise <jon@php.net>
 * @version $Revision: 308377 $
 * @package FSM
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 *
 * @example rpn.php     A Reverse Polish Notation (RPN) calculator.
 */
class FSM
{

    /**
     * Represents the initial state of the machine.
     *
     * @protected string
     * @see $currentState
     * @access private
     */
    protected $initialState = '';

    /**
     * Contains the current state of the machine.
     *
     * @protected string
     * @see $initialState
     * @access private
     */
    protected $currentState = '';

    /**
     * Contains the payload that will be passed to each action public function.
     *
     * @protected mixed
     * @access private
     */
    protected $payload = null;

    /**
     * Maps (inputSymbol, currentState) --> (action, nextState).
     *
     * @protected array
     * @see $initialState, $currentState
     * @access private
     */
    protected $transitions = array();

    /**
     * Maps (currentState) --> (action, nextState).
     *
     * @protected array
     * @see $inputState, $currentState
     * @access private
     */
    protected $transitionsAny = array();

    /**
     * Contains the default transition that is used if no more appropriate
     * transition has been defined.
     *
     * @protected array
     * @access private
     */
    protected $defaultTransition = null;

    /**
     * This method constructs a new Finite State Machine (FSM) object.
     *
     * In addition to defining the machine's initial state, a "payload" may
     * also be specified.  The payload represents a variable that will be
     * passed along to each of the action public functions.  If the FSM is being used
     * for parsing, the payload is often a array that is used as a stack.
     *
     * @param   string  $initialState   The initial state of the FSM.
     * @param   mixed   $payload        A payload that will be passed to each
     *                                  action public function.
     */
    public function __construct($initialState, &$payload = null)
    {
        $this->initialState = $initialState;
        $this->currentState = $initialState;
        $this->payload = &$payload;
    }

    /**
     * This method returns the machine's current state.
     *
     * @return  string  The machine's current state.
     *
     * @since 1.3.1
     */
    public function getCurrentState()
    {
        return $this->currentState;
    }

    /**
     * This method resets the FSM by setting the current state back to the
     * initial state (set by the constructor).
     */
    public function reset()
    {
        $this->currentState = $this->initialState;
    }

    /**
     * This method adds a new transition that associates:
     *
     *      (symbol, currentState) --> (nextState, action)
     *
     * The action may be set to NULL, in which case the processing routine
     * will ignore the action and just set the next state.
     *
     * @param   string  $symbol         The input symbol.
     * @param   string  $state          This transition's starting state.
     * @param   string  $nextState      This transition's ending state.
     * @param   string  $action         The name of the public function to invoke
     *                                  when this transition occurs.
     *
     * @see     addTransitions()
     */
    public function addTransition($symbol, $state, $nextState, $action = null)
    {
        $this->transitions[$this->generateKey($symbol, $state)] = array($nextState, $action);
    }

    /**
     * This method adds the same transition for multiple different symbols.
     *
     * @param   array   $symbols        A list of input symbols.
     * @param   string  $state          This transition's starting state.
     * @param   string  $nextState      This transition's ending state.
     * @param   string  $action         The name of the public function to invoke
     *                                  when this transition occurs.
     *
     * @see     addTransition()
     */
    public function addTransitions($symbols, $state, $nextState, $action = null)
    {
        foreach ($symbols as $symbol)
        {
            $this->addTransition($symbol, $state, $nextState, $action);
        }
    }

    /**
     * This method adds an array of transitions.  Each transition is itself
     * defined as an array of values which will be passed to addTransition()
     * as parameters.
     *
     * @param   array   $transitions    An array of transitions.
     *
     * @see     addTransition
     * @see     addTransitions
     *
     * @since 1.2.4
     */
    public function addTransitionsArray($transitions)
    {
        foreach ($transitions as $transition)
        {
            call_user_func_array(array($this, 'addTransition'), $transition);
        }
    }

    /**
     * This method adds a new transition that associates:
     *
     *      (currentState) --> (nextState, action)
     *
     * The processing routine checks these associations if it cannot first
     * find a match for (symbol, currentState).
     *
     * @param   string  $state          This transition's starting state.
     * @param   string  $nextState      This transition's ending state.
     * @param   string  $action         The name of the public function to invoke
     *                                  when this transition occurs.
     *
     * @see     addTransition()
     */
    public function addTransitionAny($state, $nextState, $action = null)
    {
        $this->transitionsAny[$state] = array($nextState, $action);
    }

    /**
     * This method sets the default transition.  This defines an action and
     * next state that will be used if the processing routine cannot find a
     * suitable match in either transition list.  This is useful for catching
     * errors caused by undefined states.
     *
     * The default transition can be removed by setting $nextState to NULL.
     *
     * @param   string  $nextState      The transition's ending state.
     * @param   string  $action         The name of the public function to invoke
     *                                  when this transition occurs.
     */
    public function setDefaultTransition($nextState, $action)
    {
        if (is_null($nextState))
        {
            $this->defaultTransition = null;
            return;
        }

        $this->defaultTransition = array($nextState, $action);
    }

    /**
     * This method returns (nextState, action) given an input symbol and
     * state.  The FSM is not modified in any way.  This method is rarely
     * called directly (generally only for informational purposes).
     *
     * If the transition cannot be found in either of the transitions lists,
     * the default transition will be returned.  Note that it is possible for
     * the default transition to be set to NULL.
     *
     * @param   string  $symbol         The input symbol.
     *
     * @return  mixed   Array representing (nextState, action), or NULL if the
     *                  transition could not be found and not default
     *                  transition has been defined.
     */
    public function getTransition($symbol)
    {
        $state = $this->currentState;

        if (!empty($this->transitions[$this->generateKey($symbol, $state)]))
        {
            return $this->transitions[$this->generateKey($symbol, $state)];
        }
        elseif (!empty($this->transitionsAny[$state]))
        {
            return $this->transitionsAny[$state];
        }
        else
        {
            return $this->defaultTransition;
        }
    }

    /**
     * This method is the main processing routine.  It causes the FSM to
     * change states and execute actions.
     *
     * The transition is determined by calling getTransition() with the
     * provided symbol and the current state.  If no valid transition is found,
     * process() returns immediately.
     *
     * The action callback may return the name of a new state.  If one is
     * returned, the current state will be updated to the new value.
     *
     * If no action is defined for the transition, only the state will be
     * changed.
     *
     * @param   string  $symbol         The input symbol.
     *
     * @see     processList()
     */
    public function process($symbol)
    {
        $transition = $this->getTransition($symbol);

        /* If a valid array wasn't returned, return immediately. */
        if (!is_array($transition) || (count($transition) != 2))
        {
            throw new Exception\InvalidTransitionException(sprintf('No transition for state %s when reading symbol %s.', $this->currentState, $symbol));
        }

        /* Update the current state to this transition's exit state. */
        $this->currentState = $transition[0];

        /* If an action for this transition has been specified, execute it. */
        if (!empty($transition[1]))
        {
            $params = array($symbol);
            if (null !== $this->payload)
            {
                $params[] = &$this->payload;
            }
            $state = call_user_func_array($transition[1], $params);

            /* If a new state was returned, update the current state. */
            if (!empty($state) && is_string($state))
            {
                $this->currentState = $state;
            }
        }
    }

    /**
     * This method processes a list of symbols.  Each symbol in the list is
     * sent to process().
     *
     * @param   array   $symbols        List of input symbols to process.
     */
    public function processList(array $symbols)
    {
        foreach ($symbols as $symbol)
        {
            $this->process($symbol);
        }
    }

    /**
     * This method processes a string of symbols.  Each symbol in the string is
     * sent to process().
     *
     * @param   string   $symbols        String of input symbols to process.
     */
    public function processString($symbols)
    {
        for ($cont = 0; $cont < strlen($symbols); $cont++)
        {
            $this->process($symbols[$cont]);
        }
    }

    /**
     * This method generate a key to associate a symbol and a state so it can be used as a key in a array.
     *
     * @param   string   $symbol        String of input symbol
     * @param   string   $state         String of input state
     * @return  string
     *
     */
    protected function generateKey($symbol, $state)
    {
        return sha1($symbol . $state);
    }

    /**
     * Return the payload used as internal memory
     *
     * @return mixed
     *
     */
    public function getPayload()
    {
        return $this->payload;
    }

}
