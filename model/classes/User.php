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

    public function __construct($email, $username, $textmap)
    {
        $this->setValue('email', $email);
        $this->setValue('username', $username);
        $this->setValue('textmap', $textmap);
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