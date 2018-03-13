<?php
/**
 *  Provides methods for other classes to use for 
 *  validation purposes.  
 */

/**
 *  Provides methods for other classes to use for 
 *  validation purposes.
 *  
 *  @author Jacob Landowski
 */
abstract class Validator extends DataCore
{    
    protected $_errors = ['not_checked' => null];

  //=========================================================//
 //                   PUBLIC FUNCTIONS                      //
//=========================================================//

    public function getErrors()
    {
        return $this->_errors;
    }

  //=========================================================//
 //                 PROTECTED FUNCTIONS                     //
//=========================================================//

    protected function hasValidated()
    {
        unset($this->_errors['not_checked']);
    }

    protected function _validateField($name, $missingMessage, $invalidMessage, $valid)
    {
        if(isset($_POST[$name]) && !empty($_POST[$name]))
        {
            $this->setValue($name, $_POST[$name]);
            
            if(!$valid($_POST[$name]))
                $this->_errors[$name] = $invalidMessage;
        }   
        else
        {
            $this->setValue($name, null);
            $this->_errors[$name] = $missingMessage;
        }
    }
}