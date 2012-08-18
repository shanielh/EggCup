<?php
namespace EggCup\Exceptions;
	
/**
 * An exception thats thrown
 * whenever an operation that is not supported
 * is called.
 *
 * @package default
 * @author Shani Elharrar
 **/
class NotSupportedException extends \LogicException
{
	/**
	 * Creates an instance of NotSupportedException
	 *
	 * @return NotSupportedException
	 **/
	public function __construct($message, $code = 0, \Exception $previous = null) {        
        parent::__construct($message, $code, $previous);
    }

    public function __toString() {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }

}
