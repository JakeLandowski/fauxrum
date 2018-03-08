<?php

abstract class CustomError
{
    public static final function throw($message, $level=E_USER_ERROR) 
    { 
        $caller = next(debug_backtrace()); 
        trigger_error("$message in {$caller['function']} 
                       called from {$caller['file']}
                       on line {$caller['line']}", $level); 
    }  
}