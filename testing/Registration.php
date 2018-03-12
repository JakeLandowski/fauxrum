<?php
/**
 *  Class used to validate and register a user.  
 *
 *  DataCore => Validator => Registration
 */

/**
 *  Class used to validate and register a user.
 *  
 *  @author Jacob Landowski
 */
class Registration extends Validator 
{
    protected $data = 
    [
        'email'    => null,
        'username' => null,
        'password' => null
    ];
    
  //=========================================================//
 //                   PUBLIC FUNCTIONS                      //
//=========================================================//

    /**
     *  Validates email, username and password on the registration page.
     *  Populates errors array with errors which can later be retrieved.
     *  Also stores the values validated for stickiness.
     */
    public function validate()
    { 
        $this->hasValidated(); // Used to prove this object has ran validation 

        $missingEmail = 'Please enter an email';
        $invalidEmail = 'Please enter a valid email ex: email@place.com';
        $missingUserName = 'Please enter a username';
        $invalidUserName = 'Username must be between 3-20 characters A-Z, a-z and 0-9 only.';
        $missingPassword = 'Please enter a password';
        $invalidPassword = 'Password must be 8 or more characters, 
                            and atleast 1 uppercase, 1 lowercase, 1 digit';

        $this->_validateField('email', $missingEmail, $invalidEmail,
        function($value)
        {
            if(filter_var($value, FILTER_VALIDATE_EMAIL))
            {
                $whereThisEmail = (new Condition('User'))->col('email')->equals($value);
                $result = Database::SELECT('email', 'User', ['condition' => $whereThisEmail]);

                if($result['num_rows'] > 0)
                {
                    $this->_errors['email'] = 'This email is already taken';
                }

                return true; // skip invalidEmail message given before
            }

            return false; // apply invalidEmail message
        });

        $this->_validateField('username', $missingUserName, $invalidUserName, 
        function($value)
        {
            if(preg_match('/^[0-9a-z]{3,20}$/i', $value))
            {
                $whereThisUserName = (new Condition('User'))->col('username')->equals($value);
                $result = Database::SELECT('email', 'User', ['condition' => $whereThisUserName]);

                if($result['num_rows'] > 0)
                {
                    $this->_errors['username'] = 'This username is already taken';
                }

                return true; // skip invalidUserName message given before
            }

            return false; // apply invalidUserName message
        });

        $this->_validateField('password', $missingPassword, $invalidPassword, 
        function($value)
        {
            return preg_match('/[A-Z]/', $value) && 
                   preg_match('/[a-z]/', $value) && 
                   preg_match('/[0-9]/', $value) &&
                   strlen($value) >= 8;
        });
    }

    /**
     *  Registers the user if there are no errors, returns false 
     *  if there are.
     * 
     *  @return boolean True if no errors and user registered successfully
     */
    public function registerUser()
    {
        if(count($this->_errors) == 0)
        {
            $email    = $this->getValue('email');
            $username = $this->getValue('username');
            $password = $this->getValue('password');
            $result = Database::INSERT('User', ['email', 'username', 'password'], 
                                               [$email,  $username,  $password]);
            print_r($result);
            return $result;
        }

        return false;
    }

  //=========================================================//
 //                   PRIVATE FUNCTIONS                     //
//=========================================================//

}