<?php
/**
 *   
 */

/**
 *  
 *  
 *  @author Jacob Landowski
 */
class Post extends DataCore 
{
    protected $data = 
    [
        'id'            => null,
        'thread'        => null,
        'owner'         => null,
        'content'       => null,
        'bot_generated' => null,
        'is_root_post'  => null
    ];

  //=========================================================//
 //                      CONSTRUCTORS                       //
//=========================================================//

    public function __construct($thread, $owner, $content, $root_post=false, $bot_generated=false)
    {
        $this->setValue('thread', $thread);
        $this->setValue('owner', $owner);
        $this->setValue('content', $content);
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