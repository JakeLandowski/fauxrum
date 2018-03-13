<?php
/**
 *  Class used to represent a post and create posts.
 *   
 *  DataCore => Validator => Registration
 */

/**
 *  Class used to represent a post and create posts.
 *  
 *  @author Jacob Landowski
 */
class Post extends Validator 
{
    protected $data = 
    [
        'id'            => null,
        'thread'        => null,
        'owner'         => null,
        'content'       => null,
        'is_root_post'  => null,
        'bot_generated' => null
    ];

  //=========================================================//
 //                      CONSTRUCTORS                       //
//=========================================================//

    public function __construct($id=null, $thread=null, $owner=null, 
                                $content=null, $root_post=false, $bot_generated=false)
    {
        $this->setValue('id', $id);
        $this->setValue('thread', $thread);
        $this->setValue('owner', $owner);
        $this->setValue('content', $content);
        $this->setValue('root_post', $root_post);
        $this->setValue('bot_generated', $bot_generated);
    }

  //=========================================================//
 //                   PUBLIC FUNCTIONS                      //
//=========================================================//

    /**
     *  Validates the creation of a post.
     *  Populates errors array with errors which can later be retrieved.
     *  Also stores the values validated for stickiness.
     */
    public function validate()
    { 
        $this->hasValidated(); // Used to prove this object has ran validation 

        $missingPost  = 'You must fill out a post';
        $invalidPost  = 'Post must be atleast 3 characters long and not empty';

        $this->_validateField('content', $missingPost, $invalidPost,
        function($value)
        {
            return !empty(trim($value)) && strlen(trim($value)) >= 3;
        });
    }

    public function createPost()
    {
        if(count($this->_errors) == 0)
        {
            $thread  = $this->getValue('thread');
            $owner   = $this->getValue('owner');
            $content = $this->getValue('content');
            $is_root_post  = $this->getValue('is_root_post');
            $bot_generated = $this->getValue('bot_generated') ? 1 : 0;
             
            $result = Database::INSERT('Post', 
                ['thread', 'owner', 'content', 'is_root_post', 'bot_generated'], 
                [$thread,  $owner,  $content,  $is_root_post,  $bot_generated]);
            
            $returnValue = '';

            if(!$result['success'] || $result['num_rows'] == 0 || isset($result['duplicate']))
            {
                $returnValue = 'Sorry, something went wrong with post creation';
            }
            else if($result['id']) // SUCCESSFUL POST CREATION
            {
                $this->setValue('id', $result['id']);
                $returnValue = $this;
            }

            return $returnValue; // Return This Post Object or Error Message
        }
        else // If whoever uses this class forgets to check for errors
        {
            CustomError::throw('Tried to INSERT new Post 
                                when there are still errors.', 2);
        }   
    }


  //=========================================================//
 //                   PRIVATE FUNCTIONS                     //
//=========================================================//

}