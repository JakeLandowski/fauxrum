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
        'owner_name'    => null,
        'content'       => null,
        'created'       => null,
        'is_root_post'  => false,
        'bot_generated' => false
    ];
    
  //=========================================================//
 //                   PUBLIC FUNCTIONS                      //
//=========================================================//

    public function deletePost()
    {
        $postId = $this->getValue('id');
        $whereThisPost = (new Condition('Post'))->col('id')->equals($postId);
        $result = Database::DELETE('Post', $whereThisPost);

        $success = $result['success'] && $result['num_rows'] > 0;

        if($success)
        {
            //  should decrement thread views here possibly
        }

        return $success;
    }

    public function editContent($newContent)
    {
        $postId = $this->getValue('id');
        $whereThisPost = (new Condition('Post'))->col('id')->equals($postId);
        Database::UPDATE('Post', 'content', $newContent, $whereThisPost); 
        $this->setValue('content', $newContent);
    }

    public static function getAllFromDatabase($limitStart, $limitAmount, $orderBy, $threadId)
    {
        if(!isset($threadId))
            CustomError::throw('Post::getAllFromDatabase() requires a thread 
                                id as the 4th parameter.', 2);

        $options = 
        [
            'condition' => (new Condition('Post'))->col('thread')->equals($threadId),
            'order_by'     => $orderBy,
            'limit_amount' => $limitAmount,
            'limit_start'  => $limitStart
        ];
        
        $result = Database::SELECT_ALL('Post', $options);

        $returnValue = '';

        if($result['success'] && $result['num_rows'] > 0 && isset($result['rows']))
        {
            $rows = $result['rows'];
            $returnValue = [ 'posts' => [] ];
            
            if(isset($result['total_rows'])) 
                $returnValue['total'] = $result['total_rows'];

            foreach($rows as $row)
            {
                $post = new Post;
                $post->setValue('id',            $row['id']);
                $post->setValue('owner',         $row['owner']);
                $post->setValue('owner_name',    $row['owner_name']);
                $post->setValue('thread',        $row['thread']);
                $post->setValue('content',       $row['content']);
                $post->setValue('created',       $row['created']);
                $post->setValue('is_root_post',  $row['is_root_post']);
                $post->setValue('bot_generated', $row['bot_generated']);
                $returnValue['posts'][] = $post;
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
     * 
     */
    public static function getPost($postId)
    {
        $options = 
        [
            'fetch' => Database::ONE,
            'condition' => (new Condition('Post'))->col('id')->equals($postId) 
        ];
        
        $result = Database::SELECT_ALL('Post', $options);

        $returnValue = '';

        if($result['success'] && $result['num_rows'] > 0 && isset($result['row']))
        {
            $post = new Post;
            $post->setValue('id',            $result['row']['id']);
            $post->setValue('owner',         $result['row']['owner']);
            $post->setValue('owner_name',    $result['row']['owner_name']);
            $post->setValue('content',       $result['row']['content']);
            $post->setValue('thread',        $result['row']['thread']);
            $post->setValue('created',       $result['row']['created']);
            $post->setValue('is_root_post',  $result['row']['is_root_post']);
            $post->setValue('bot_generated', $result['row']['bot_generated']);
            $returnValue = $post;
        }
        else if($result['num_rows'] == 0)
        {
            $returnValue = 'This post doesn\'t exist';
        }
        else
        {
            $returnValue = 'Something went wrong fetching post contents';
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
        $invalidPost  = 'Post must be between 5-1000 characters and not empty';

        $this->_validateField('content', $missingPost, $invalidPost,
        function($value)
        {
            return !empty(trim($value)) && strlen(trim($value)) >= 5 && strlen($value) <= 1000;
        });
    }

    public function createPost()
    {
        if(count($this->_errors) == 0)
        {
                // Set Arguments for INSERT
            $thread     = $this->getValue('thread');
            $owner      = $this->getValue('owner');
            $owner_name = $this->getValue('owner_name');
            $content    = $this->getValue('content');
            $is_root_post  = $this->getValue('is_root_post')  ? 1 : 0;
            $bot_generated = $this->getValue('bot_generated') ? 1 : 0;

                // Search for this Thread's existence before attempting to INSERT Post
            $whereThisThread = (new Condition('Thread'))->col('id')->equals($thread);
            $threadResult = Database::SELECT('id', 'Thread', ['condition' => $whereThisThread]);
            
                // This Thread exists, commence Post Insertion
            if($threadResult['success'] && $threadResult['num_rows'] > 0)
            {
                $result = Database::INSERT('Post', 
                ['thread', 'owner', 'owner_name', 'content', 'is_root_post', 'bot_generated'], 
                [$thread,  $owner,  $owner_name, $content,  $is_root_post,  $bot_generated]);
            
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