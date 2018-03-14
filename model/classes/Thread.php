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
        'title'         => null,
        'replies'       => 0,
        'views'         => 0,
        'created'       => null,
        'bot_generated' => false,
        'root_post'     => null // not a column in database
    ];

  //=========================================================//
 //                   PUBLIC FUNCTIONS                      //
//=========================================================//

    public static function getThreads()
    {
        $options = 
        [
            'order_by' => 'created',
            'descending' => true
        ];
        
        $result = Database::SELECT_ALL('Thread', $options);

        $returnValue = '';

        if($result['success'] && $result['num_rows'] > 0 && isset($result['rows']))
        {
            $rows = $result['rows'];
            $returnValue = [];
            
            foreach($rows as $row)
            {
                $thread = new Thread;
                $thread->setValue('id',            $row['id']);
                $thread->setValue('owner',         $row['owner']);
                $thread->setValue('title',         $row['title']);
                $thread->setValue('created',       $row['created']);
                $thread->setValue('bot_generated', $row['bot_generated']);
                $returnValue[] = $thread;
            }
        }
        else if($result['num_rows'] == 0)
        {
            $returnValue = 'This thread doesn\'t exist';
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
            $thread->setValue('created',       $result['row']['created']);
            $thread->setValue('bot_generated', $result['row']['bot_generated']);
            $returnValue = $thread;
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
        $this->hasValidated(); // Used to prove this object has ran validation 

        $missingTitle = 'Please create a thread title';
        $invalidTitle = 'Title must be between 3-40 characters or less and not empty';

        $this->_validateField('title', $missingTitle, $invalidTitle,
        function($value)
        {
            if(!empty(trim($value)) && strlen($value) <= 40 && strlen(trim($value)) >= 3)
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
            $bot_generated = $this->getValue('bot_generated') ? 1 : 0;
             
            $result = Database::INSERT('Thread', ['owner', 'title', 'bot_generated'], 
                                                 [$owner,  $title,  $bot_generated]);
            $returnValue = '';

            if(isset($result['duplicate'])) // TITLE TAKEN
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
                $post->setValue('owner', $this->getValue('owner'));
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

  //=========================================================//
 //                   PRIVATE FUNCTIONS                     //
//=========================================================//

}