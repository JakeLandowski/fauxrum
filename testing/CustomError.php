<?php

abstract class CustomError
{
    public static final function throw($message, $depth=1, $level=E_USER_ERROR) 
    { 
        $caller = debug_backtrace()[$depth]; 
        trigger_error("$message In {$caller['function']} 
                       called from {$caller['file']}
                       on line {$caller['line']}", $level); 
    }  
}