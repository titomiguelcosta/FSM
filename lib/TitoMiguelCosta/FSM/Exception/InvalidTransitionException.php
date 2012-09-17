<?php

namespace TitoMiguelCosta\FSM\Exception;

/**
 * InvalidTransitionException is thrown when current state reads a symbol that does not lead to other state
 * 
 * @package FSM
 * @author titomiguelcosta
 */
class InvalidTransitionException extends \Exception
{
}