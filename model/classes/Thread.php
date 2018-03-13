<?php
/**
 *  Class used to represent a thread
 *   
 *  DataCore => Validator => Registration
 */

/**
 *  
 *  
 *  @author Jacob Landowski
 */
class Thread extends Validator 
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

    /**
     *  Validates the creation of a thread on new-thread page..
     *  Populates errors array with errors which can later be retrieved.
     *  Also stores the values validated for stickiness.
     */
    public function validate()
    { 
        $this->hasValidated(); // Used to prove this object has ran validation 

        $missingTitle = 'Please create a thread title';
        $invalidTitle = 'Title must 40 characters or less and not empty';
        $missingPost  = 'You must fill out a post to submit a thread';
        $invalidPost  = 'Post must be atleast 3 characters long and not empty';

        $this->_validateField('title', $missingTitle, $invalidTitle,
        function($value)
        {
            if(!empty(trim($value)) && strlen($value) <= 40)
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

  //=========================================================//
 //                   PRIVATE FUNCTIONS                     //
//=========================================================//

}