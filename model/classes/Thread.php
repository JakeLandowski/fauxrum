<?php
/**
 *   
 */

/**
 *  
 *  
 *  @author Jacob Landowski
 */
class Thread extends DataCore 
{
    protected $data = 
    [
        'id'            => null,
        'title'         => null,
        'bot_generated' => null,
        'root_post'     => null // not a column in database
    ];

  //=========================================================//
 //                      CONSTRUCTORS                       //
//=========================================================//

    public function __construct($title, $root_post, $bot_generated=false)
    {
        $this->setValue('title', $title);
        $this->setValue('root_post', $root_post);
        $this->setValue('bot_generated', $bot_generated);
    }

  //=========================================================//
 //                   PUBLIC FUNCTIONS                      //
//=========================================================//




  //=========================================================//
 //                   PRIVATE FUNCTIONS                     //
//=========================================================//

}