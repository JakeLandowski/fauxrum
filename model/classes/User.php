<?php
/**
 *  Class to represent the User.
 */

/**
 *  Class to represent the User.
 *  
 *  @author Jacob Landowski
 */
class User extends DataCore 
{
    protected $data = 
    [
        'id'          => null,
        'email'       => null,
        'username'    => null,
        'textmap'     => null,
        'num_threads' => 0,
        'num_posts'   => 0
    ];

  //=========================================================//
 //                      CONSTRUCTORS                       //
//=========================================================//

    public function __construct($id=null, $email=null, $username=null, $textmap=null)
    {
        $this->setValue('id', $id);
        $this->setValue('email', $email);
        $this->setValue('username', $username);
        $this->setValue('textmap', $textmap);
    }

  //=========================================================//
 //                   PUBLIC FUNCTIONS                      //
//=========================================================//

    public function parseThread($thread)
    {
        if(!$thread instanceof Thread)
            CustomError::throw("Tried to pass non-Thread 
                                object $thread to parseThread().", 2);
        $map = $this->getValue('textmap');
        
        if($map instanceof TextMap) // For safety
        {
            $map->parseText($thread->getValue('title'));
            $map->parseSentences($thread->getValue('root_post')->getValue('content'));
            if(loggedIn()) 
                $map->markAsParsedLater('threads', $thread->getValue('id'));
        }
    }

    public function parsePost($post)
    {
        if(!$post instanceof Post)
            CustomError::throw("Tried to pass non-Post 
                                object $post to parsePost().", 2);
        $map = $this->getValue('textmap');
        
        if($map instanceof TextMap) // For safety
        {
            $map->parseSentences($post->getValue('content'));
            if(loggedIn()) 
                $map->markAsParsedLater('posts', $post->getValue('id'));
        }
    }

    public function generateThread()
    {
        $thread    = new Thread;
        $owner     = $this->getValue('id');
        $ownerName = $this->getValue('username');
        $map       = $this->getValue('textmap');
    
        // dont forget to track number of threads/posts in User table later, change database
        
        $randomTitle   = $map->generate(20, 40);
        $randomContent = $map->generate(rand(100, 500));
        $thread->setUpGeneratedThread($randomTitle, $randomContent, $owner, $ownerName);
        $threadResult = $thread->createThread();
        
        // if($threadResult instanceof Thread)
        // {
            
        // }
        // else
        // {

        // }
    }

    public function generatePost()
    {
        $result = Database::SELECT(['id', 'replies'], 'Thread');
        
        if($result['success'] && $result['num_rows'] > 0 && isset($result['rows']))
        {
            $randomThreadId = rand(0, $result['num_rows'] - 1);
            $threadRow = $result['rows'][$randomThreadId];
            $threadId  = $threadRow['id'];

            $post      = new Post;
            $owner     = $this->getValue('id');
            $ownerName = $this->getValue('username');
            $map       = $this->getValue('textmap');
            
            $randomContent = $map->generate(rand(100, 500));
            $post->setUpGeneratedPost($randomContent, $owner, $ownerName, $threadId);
            $postResult = $post->createPost();

            if($postResult instanceof Post)
            {
                $thread = new Thread;
                $thread->setValue('id', $threadId);
                $thread->setValue('replies', $threadRow['replies']);
                $thread->incrementReplies();
            }
            else
            {

            }
        }
    }

    public function saveMap()
    {
        // to be called in logout
        
        // if toMarkLater has things
            // update map to database
            // update all the threads/posts as parsed = true 
    }

  //=========================================================//
 //                   PRIVATE FUNCTIONS                     //
//=========================================================//

}