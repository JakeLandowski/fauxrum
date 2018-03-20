<?php
/**
 *  Class used to represent a thread and create threads.
 *   
 *  DataCore => Validator => Registration
 */

/**
 *  Class used to represent a thread and create threads.
 *  
 *  @author Jacob Landowski
 */
class Thread extends Validator
{
    protected $data = 
    [
        'id'            => null,
        'owner'         => null,
        'owner_name'    => null,
        'title'         => null,
        'replies'       => 0,
        'views'         => 0,
        'created'       => null,
        'last_reply'    => null,
        'bot_generated' => false,
        'parsed'        => false,
        'root_post'     => null // not a column in database
    ];

  //=========================================================//
 //                   PUBLIC FUNCTIONS                      //
//=========================================================//

    public function deleteThread()
    {
        $threadId = $this->getValue('id');
        $whereThisThread = (new Condition('Thread'))->col('id')->equals($threadId);
        $result = Database::DELETE('Thread', $whereThisThread);
        return $result['success'] && $result['num_rows'] > 0;
    }

    public function editTitle($newTitle)
    {
        $threadId = $this->getValue('id');
        $whereThisThread = (new Condition('Thread'))->col('id')->equals($threadId);
        Database::UPDATE('Thread', 'title', $newTitle, $whereThisThread); 
        $this->setValue('title', $newTitle);
    }

    public function incrementReplies()
    {
        $threadId = $this->getValue('id');
        
        $replies = $this->getValue('replies');
        $replies++; 
        $this->setValue('replies', $replies);
        
        $whereThisThread = (new Condition('Thread'))->col('id')->equals($threadId);
        Database::UPDATE('Thread', 'replies', $replies, $whereThisThread);
    }

    public function incrementViews($userId)
    {
        if($this->getValue('owner') != $userId)
        {
            $threadId = $this->getValue('id');
            
            $result = Database::INSERT('Thread_User_Views', ['thread', 'user'], 
                                                            [$threadId, $userId]);
            
            if($result['success'] && !isset($result['duplicate'])) // NOT VIEWED
            {
                $views = $this->getValue('views');
                $views++; 
                $this->setValue('views', $views);
                
                $whereThisThread = (new Condition('Thread'))->col('id')->equals($threadId);
                Database::UPDATE('Thread', 'views', $views, $whereThisThread);
            }
        }
    }

    public static function getNumThreads()
    {
        $result = Database::SELECT('id', 'Thread');
        if(isset($result['success']) && $result['success'] && isset($result['num_rows'])) 
            return $result['num_rows'];
        else
            return 0;
    }

    public static function getAllFromDatabase($limitStart, $limitAmount, $orderBy)
    {
        $options = 
        [
            'order_by'     => $orderBy,
            'descending'   => true,
            'limit_amount' => $limitAmount,
            'limit_start'  => $limitStart
        ];
        
        $result = Database::SELECT_ALL('Thread', $options);

        $returnValue = '';

        if($result['success'] && $result['num_rows'] > 0 && isset($result['rows']))
        {
            $rows = $result['rows'];
            $returnValue = [ 'threads' => [] ];

            if(isset($result['total_rows'])) 
                $returnValue['total'] = $result['total_rows'];  
            
            foreach($rows as $row)
            {
                $thread = new Thread;
                $thread->setValue('id',            $row['id']);
                $thread->setValue('owner',         $row['owner']);
                $thread->setValue('owner_name',    $row['owner_name']);
                $thread->setValue('title',         $row['title']);
                $thread->setValue('created',       $row['created']);
                $thread->setValue('last_reply',    $row['last_reply']);
                $thread->setValue('views',         $row['views']);
                $thread->setValue('replies',       $row['replies']);
                $thread->setValue('bot_generated', $row['bot_generated']);
                $returnValue['threads'][] = $thread;
            }
        }
        else if($result['num_rows'] == 0)
        {
            $returnValue = 'There are no threads';
        }
        else
        {
            $returnValue = 'Something went wrong fetching thread';
        }

        return $returnValue;
    }

    public static function getThread($threadId)
    {
        $options = 
        [
            'fetch' => Database::ONE,
            'condition' => (new Condition('Thread'))->col('id')->equals($threadId) 
        ];
        
        $result = Database::SELECT_ALL('Thread', $options);

        $returnValue = '';

        if($result['success'] && $result['num_rows'] > 0 && isset($result['row']))
        {
            $thread = new Thread;
            $thread->setValue('id',            $result['row']['id']);
            $thread->setValue('title',         $result['row']['title']);
            $thread->setValue('owner',         $result['row']['owner']);
            $thread->setValue('owner_name',    $result['row']['owner_name']);
            $thread->setValue('created',       $result['row']['created']);
            $thread->setValue('last_reply',    $result['row']['last_reply']);
            $thread->setValue('views',         $result['row']['views']);
            $thread->setValue('replies',       $result['row']['replies']);
            $thread->setValue('bot_generated', $result['row']['bot_generated']);
            $returnValue = $thread;
        }
        else if($result['num_rows'] == 0)
        {
            $returnValue = 'This thread doesn\'t exist';
        }
        else
        {
            $returnValue = 'Something went wrong fetching thread title';
        }

        return $returnValue;
    }

    /**
     *  Validates the creation of a thread on new-thread page..
     *  Populates errors array with errors which can later be retrieved.
     *  Also stores the values validated for stickiness.
     */
    public function validate()
    {
        $this->validateTitle();
        $this->validateRootPost();
    }

    /**
     *  Validates the title of a thread on current page..
     *  Populates errors array with errors which can later be retrieved.
     *  Also stores the values validated for stickiness.
     */
    public function validateTitle()
    {
        $this->hasValidated(); // Used to prove this object has ran validation

        $missingTitle = 'Please create a thread title';
        $invalidTitle = 'Title must be between 5-40 characters or less and not empty';

        $this->_validateField('title', $missingTitle, $invalidTitle,
        function($value)
        {
            if(!empty(trim($value)) && strlen($value) <= 40 && strlen(trim($value)) >= 5)
            {
                $whereThisTitle = (new Condition('Thread'))->col('title')->equals($value);
                $result = Database::SELECT('title', 'Thread', ['condition' => $whereThisTitle]);

                if($result['success'] && $result['num_rows'] > 0)
                {
                    $this->_errors['title'] = 'This title was already used';
                }

                return true; // skip invalidTitle message given before
            }

            return false; // apply invalidTitle message
        });
    }

    /**
     *  Validates the root post of a thread on the current page..
     *  Populates errors array with errors which can later be retrieved.
     *  Also stores the values validated for stickiness.
     */
    public function validateRootPost()
    {
        $this->hasValidated(); // Used to prove this object has ran validation
        
            // Make and validate a Post after validating Thread
        $post = new Post;
        $this->setValue('root_post', $post);
        $post->validate();

            // Forward errors to Parent Thread 
        $this->_errors = array_merge($this->_errors, $post->getErrors());
    }

    public function createThread()
    {
        if(count($this->_errors) == 0)
        {
                // Set Thread arguments for INSERT
            $title = $this->getValue('title');
            $owner = $this->getValue('owner');
            $owner_name    = $this->getValue('owner_name');
            $bot_generated = $this->getValue('bot_generated') ? 1 : 0;
            $parsed        = $this->getValue('parsed')        ? 1 : 0;

            $result = Database::INSERT('Thread', ['owner', 'owner_name', 'title', 'bot_generated', 'parsed'], 
                                                 [$owner,  $owner_name, $title, $bot_generated, $parsed]);
            $returnValue = '';

            if(isset($result['duplicate'])) // TITLE TAKEN or foreign key issue lol
            {
                $returnValue = 'Sorry, but this thread has already been created, 
                                somebody might have beat you to it';
            }
            else if(!$result['success'] || $result['num_rows'] == 0) // UNKNOWN PROBLEM
            {
                $returnValue = 'Sorry, something went wrong with the thread creation';
            }
            else if($result['id']) // SUCCESSFUL THREAD CREATION
            {
                $returnValue = $this;
                $this->setValue('id', $result['id']);

                    //  Set Post arguments for INSERT
                $post = $this->getValue('root_post'); // Grab Post object created earlier
                $post->setValue('thread', $this->getValue('id'));
                $post->setValue('owner', $owner);
                $post->setValue('owner_name', $owner_name);
                $post->setValue('is_root_post', true);
                $postResult = $post->createPost(); // Attempt INSERT
                
                    // Failed Post insertion
                    // Delete Thread that was inserted
                    // Forward Post error
                if(!$postResult instanceof Post)
                {
                    $returnValue = $postResult;
                    $id = $this->getValue('id');
                    $whereThisThread = (new Condition('Thread'))->col('id')->equals($id);
                    Database::DELETE('Thread', $whereThisThread);
                }
            }

            return $returnValue; // Return This Thread Object or Error Message
        }
        else // If whoever uses this class forgets to check for errors
        {
            CustomError::throw('Tried to INSERT new Thread in new-thread 
                                when there are still errors.', 2);
        }
    }

    public function setUpGeneratedThread($generatedTitle, $generatedContent, $owner, $ownerName)
    {
        $this->hasValidated(); // mark as validated because its all gooooood
        $post = new Post;
        $post->setUpGeneratedPost($generatedContent, $owner, $ownerName);
        $this->setValue('root_post', $post);
        $this->setValue('owner', $owner);
        $this->setValue('owner_name', $ownerName);
        $this->setValue('bot_generated', true);
        $this->setValue('parsed', true);
        $this->setValue('title', ucwords($generatedTitle));
    }

  //=========================================================//
 //                   PRIVATE FUNCTIONS                     //
//=========================================================//

}