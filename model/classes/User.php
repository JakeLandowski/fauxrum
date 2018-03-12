<?php
/**
 *   
 */

/**
 *  
 *  
 *  @author Jacob Landowski
 */
class User extends DataCore 
{
    protected $data = 
    [
        'id'       => null,
        'email'    => null,
        'username' => null,
        'textmap'  => null
    ];

  //=========================================================//
 //                      CONSTRUCTORS                       //
//=========================================================//

    public function __construct($email, $username)
    {
        $this->setValue('email', $email);
        $this->setValue('username', $username);
    }

  //=========================================================//
 //                   PUBLIC FUNCTIONS                      //
//=========================================================//


    public function save()
    {
        
    }


  //=========================================================//
 //                   PRIVATE FUNCTIONS                     //
//=========================================================//

}