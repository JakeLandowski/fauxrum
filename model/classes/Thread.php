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
        'bot_generated' => false,
        'root_post'     => null // not a column in database
    ];

  //=========================================================//
 //                      CONSTRUCTORS                       //
//=========================================================//

    public function __construct($id=null, $title=null, $root_post=null, $bot_generated=false)
    {
        $this->setValue('id', $id);
        $this->setValue('title', $title);
        $this->setValue('root_post', $root_post);
        $this->setValue('bot_generated', $bot_generated);
    }

  //=========================================================//
 //                   PUBLIC FUNCTIONS                      //
//=========================================================//

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
            if(!empty(trim($value)) && strlen($value) <= 40 && strlen($value) >= 3)
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

        $post = new Post;
        $this->setValue('root_post', $post);
        $post->validate();
        $this->_errors = array_merge($this->_errors, $post->getErrors());
    }

    public function createThread()
    {
        if(count($this->_errors) == 0)
        {
            $title = $this->getValue('title');
            $owner = $this->getValue('owner');
            $bot_generated = $this->getValue('bot_generated') ? 1 : 0;
             
            $result = Database::INSERT('Thread', ['owner', 'title', 'bot_generated'], 
                                                 [$owner,  $title,  $bot_generated]);
            $returnValue = '';

            if(isset($result['duplicate']))
            {
                $returnValue = 'Sorry, but this thread has already been created, 
                                somebody might have beat you to it';
            }
            else if(!$result['success'] || $result['num_rows'] == 0)
            {
                $returnValue = 'Sorry, something went wrong with the thread creation';
            }
            else if($result['id']) // SUCCESSFUL THREAD CREATION
            {
                $returnValue = $this;
                $this->setValue('id', $result['id']);

                $post = $this->getValue('root_post');
                $post->setValue('thread', $this->getValue('id'));
                $post->setValue('owner', $this->getValue('owner'));
                $post->setValue('is_root_post', true);
                $postResult = $post->createPost();
                
                if(!$postResult instanceof Post)
                {
                    $returnValue = $postResult;
                    // failed, delete thread that was inserted
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