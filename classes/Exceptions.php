<?php
namespace ClearMarkup\Classes;

/**
 * Class Exceptions
 * 
 * This class provides a method to get error messages based on the type of exception.
 */
class Exceptions
{
    /**
     * Get error message based on the type of exception.
     * 
     * @param mixed $exception The exception object.
     * @return string The error message.
     */
    public function getMessage($exception)
    {
        if ($exception instanceof \InvalidArgumentException) {
            return 'Invalid argument';
        } elseif ($exception instanceof \RuntimeException) {
            return 'Runtime error';
        } elseif ($exception instanceof \Exception) {
            return 'General exception';
        } else {
            return 'Unknown error';
        }
    }
}