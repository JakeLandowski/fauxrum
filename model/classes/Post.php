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
        'created'       => null,
        'is_root_post'  => false,
        'bot_generated' => false
    ];
    
  //=========================================================//
 //                   PUBLIC FUNCTIONS                      //
//=========================================================//

    public static function getPosts($threadId)
    {
        $options = 
        [
            'condition' => (new Condition('Post'))->col('thread')->equals($threadId),
            'order_by'  => 'created'
        ];
        
        $result = Database::SELECT_ALL('Post', $options);

        $returnValue = '';

        if($result['success'] && $result['num_rows'] > 0)
        {
            $rows = $result['rows'];
            $returnValue = [];
            
            foreach($rows as $row)
            {
                $post = new Post;
                $post->setValue('id',            $row['id']);
                $post->setValue('owner',         $row['owner']);
                $post->setValue('thread',        $row['thread']);
                $post->setValue('content',       $row['content']);
                $post->setValue('created',       $row['created']);
                $post->setValue('is_root_post',  $row['is_root_post']);
                $post->setValue('bot_generated', $row['bot_generated']);
                $returnValue[] = $post;
            }
        }
        else if($result['num_rows'] == 0)
        {
            $returnValue = 'This thread doesn\'t exist';
        }
        else
        {
            $returnValue = 'Something went wrong fetching posts';
        }

        return $returnValue;
    }

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
                // Set Arguments for INSERT
            $thread  = $this->getValue('thread');
            $owner   = $this->getValue('owner');
            $content = $this->getValue('content');
            $is_root_post  = $this->getValue('is_root_post')  ? 1 : 0;
            $bot_generated = $this->getValue('bot_generated') ? 1 : 0;

                // Search for this Thread's existence before attempting to INSERT Post
            $whereThisThread = (new Condition('Thread'))->col('id')->equals($thread);
            $threadResult = Database::SELECT('id', 'Thread', ['condition' => $whereThisThread]);
            
                // This Thread exists, commence Post Insertion
            if($threadResult['success'] && $threadResult['num_rows'] > 0)
            {
                $result = Database::INSERT('Post', 
                ['thread', 'owner', 'content', 'is_root_post', 'bot_generated'], 
                [$thread,  $owner,  $content,  $is_root_post,  $bot_generated]);
            
                $returnValue = '';

                    // UNKNOWN ISSUE
                if(!$result['success'] || $result['num_rows'] == 0 || isset($result['duplicate']))
                {
                    $returnValue = 'Sorry, something went wrong with post creation';
                }
                else if($result['id']) // SUCCESSFUL POST CREATION
                {
                    $this->setValue('id', $result['id']);
                    $returnValue = $this;
                }
                
            }
            else // FAILED, THIS THREAD DOESNT EXIST
            {
                $returnValue = 'Sorry but the thread you\'re 
                                trying to reply in doesn\'t exist';
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