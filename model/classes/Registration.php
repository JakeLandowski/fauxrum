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
        $invalidEmail = 'Please enter a valid email, under 50 chars ex: email@place.com';
        $missingUserName = 'Please enter a username';
        $invalidUserName = 'Username must be between 3-20 characters A-Z, a-z and 0-9 only.';
        $missingPassword = 'Please enter a password';
        $invalidPassword = 'Password must be 8 or more characters, 
                            and atleast 1 uppercase, 1 lowercase, 1 digit';
        $missingRepeatPassword = 'Please enter your password again';
        $invalidRepeatPassword = 'Repeated password must match the original';

        $this->_validateField('email', $missingEmail, $invalidEmail,
        function($value)
        {
            if(strlen($value) <= 50 && filter_var($value, FILTER_VALIDATE_EMAIL))
            {
                $whereThisEmail = (new Condition('User'))->col('email')->equals($value);
                $result = Database::SELECT('email', 'User', ['condition' => $whereThisEmail]);

                if($result['success'] && $result['num_rows'] > 0)
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

                if($result['success'] && $result['num_rows'] > 0)
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

        $this->_validateField('repeat_password', $missingRepeatPassword, $invalidRepeatPassword, 
        function($value)
        {
            $password = $this->getValue('password');
            return isset($password) && $value == $password;
        });
    }

    /**
     *  Registers the user if there are no errors, throws an error otherwise.
     *  Hashed password and attempts to INSERT user to database, if not successful
     *  returns an error message to be used in template. If successful INSERTS
     *  a fresh TextMap object and creates a new User object to represent this user.
     *  If the TextMap successfully INSERTS then it is given to the User object.
     * 
     *  @return mixed Error messages if User insertion failed otherwise User object
     */
    public function registerUser()
    {
        if(count($this->_errors) == 0)
        {
            $email    = $this->getValue('email');
            $username = $this->getValue('username');
            $password = password_hash($this->getValue('password'), PASSWORD_DEFAULT); 
            $result   = Database::INSERT('User', ['email', 'username', 'password'], 
                                                 [$email,  $username,  $password]);
            $returnValue = '';

            if(isset($result['duplicate']))
            {
                $returnValue = 'Sorry, but this account has already been created, 
                                somebody might have beat you to it';
            }
            else if(!$result['success'] || $result['num_rows'] == 0)
            {
                $returnValue = 'Sorry, something went wrong registering you';
            }
            else if($result['id']) // SUCCESSFUL REGISTRATION
            {
                $userId = $result['id']; // Get id

                $textMap = new TextMap(5, 500); // Create fresh TextMap 
                $serializedTextMap = serialize($textMap); // Prepare for INSERT

                    // Insert TextMap
                $mapResult = Database::INSERT('TextMap', ['owner', 'map_data'], 
                                                [$userId, $serializedTextMap]);
                    // Create User Object
                $returnValue = new User($userId, $email, $username);

                    // If TextMap INSERT Success
                    // Set TextMap ID and attach to User Object 
                if($mapResult['success'] && 
                   $mapResult['num_rows'] == 1 && 
                   isset($mapResult['id']))
                {
                    $textMap->setId($mapResult['id']); 
                    $returnValue->setValue('textmap', $textMap);
                }
            }

            return $returnValue; // Return User Object or Error Message
        }
        else // If whoever uses this class forgets to check for errors
        {
            CustomError::throw('Tried to INSERT new member in Registration 
                                when there are still errors.', 2);
        }
    }
}