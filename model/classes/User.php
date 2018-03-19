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

    public function fetchMapFromDatabase($online=true)
    {
        $userId = $this->getValue('id');

        $mapOptions = 
        [
            'condition' => (new Condition('TextMap'))->col('owner')->equals($userId),
            'fetch' => Database::ONE
        ];

        $mapResult = Database::SELECT(['id', 'map_data'], 'TextMap', $mapOptions);

        if($mapResult['success'] && 
            $mapResult['num_rows'] == 1 &&
            isset($mapResult['row']))
        {
            $textMap = unserialize($mapResult['row']['map_data']);
            $textMap->setId($mapResult['row']['id']); 
            $this->setValue('textmap', $textMap);
            
            $whereThisMap = (new Condition('TextMap'))->col('id')->equals($mapResult['row']['id']);
            Database::UPDATE('TextMap', 'was_used', ($online ? 0 : 1), $whereThisMap);
        }
        else // There was no textmap when logging in, make one
        {
            $textMap = new TextMap(5, 500); // Create fresh TextMap 
            $serializedTextMap = serialize($textMap); // Prepare for INSERT

                // Insert TextMap
            $mapResult = Database::INSERT('TextMap', ['owner', 'map_data'], 
                                            [$userId, $serializedTextMap]);
            $this->setValue('textmap', $textMap);
        }
    }

    public function parseThread($thread)
    {
        if(!$thread instanceof Thread)
            CustomError::throw("Tried to pass non-Thread 
                                object to parseThread().", 1);
        $map = $this->getValue('textmap');
        
        if($map instanceof TextMap) // For safety
        {
            $title = $thread->getValue('title');
            $map->parseText($title);
            $map->markAsParsedLater('threads', $thread->getValue('id'));
        }
    }

    public function parsePost($post)
    {
        if(!$post instanceof Post)
            CustomError::throw("Tried to pass non-Post 
                                object to parsePost().", 1);
        $map = $this->getValue('textmap');
        
        if($map instanceof TextMap) // For safety
        {
            $content = $post->getValue('content');
            $chunks = preg_split('/\[quote\](.*)\[\/quote\]/i', $content, -1,  PREG_SPLIT_DELIM_CAPTURE);
            // $chunk[1] == quoted content can use later for cross over algo
            $quoteLess  = isset($chunks[0]) ? $chunks[0] : '';
            $quoteLess .= isset($chunks[2]) ? $chunks[2] : '';

            $map->parseSentences($quoteLess);
            $map->markAsParsedLater('posts', $post->getValue('id'));
        }
    }

    public function generateThread()
    {
        if($this->getValue('num_threads') < 3) return;
                
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

    public function incrementNumPosts($isRootPost=false)
    {
        // UPDATE USER NUM_POST OR NUM_THREAD
        $whereThisUser = (new Condition('User'))->col('id')->equals($this->getValue('id'));
        $col = $isRootPost ? 'num_threads' : 'num_posts';
        $val = $this->getValue($col) + 1;
        $this->setValue($col, $val);
        Database::UPDATE('User', $col, $val, $whereThisUser);
    }

    public function generatePost()
    {
        if($this->getValue('num_posts') < 3) return;

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

    public function saveMap() // To be called in logout
    {
        $map = $this->getValue('textmap');
        $mapId = $map->getId();
        $whereThisMap = (new Condition('TextMap'))->col('id')->equals($mapId);

        $result = Database::SELECT('was_used', 'TextMap', 
                    ['fetch' => Database::ONE, 'condition' => $whereThisMap]);

        $shouldSave  = $result['success'] && $result['num_rows'] > 0 && $result['row']['was_used'] == 0;
        $toBeMarked  = $map->getToMarkLater();
        $shouldParse = (count($toBeMarked['threads']) > 0 || count($toBeMarked['posts']) > 0);
        
        if($shouldSave && $shouldParse)
        {
            $threads = $toBeMarked['threads'];
            $posts   = $toBeMarked['posts'];
            
            $whereThisThread;
            foreach($threads as $id)
            {
                $whereThisThread = (new Condition('Thread'))->col('id')->equals($id);
                Database::UPDATE('Thread', 'parsed', 1, $whereThisThread);
            }

            $whereThisPost;
            foreach($posts as $id)
            {
                $whereThisPost = (new Condition('Post'))->col('id')->equals($id);
                Database::UPDATE('Post', 'parsed', 1, $whereThisPost);
            }

            Database::UPDATE('TextMap', 'map_data', serialize($map), $whereThisMap);
        } 
    }

  //=========================================================//
 //                   PRIVATE FUNCTIONS                     //
//=========================================================//

}