<?php
/**
 *  Class to represent the User. Handles most of the passive/offline
 *  content parsing and generation for the user.
 */

/**
 *  Class to represent the User. Handles most of the passive/offline
 *  content parsing and generation for the user.
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

    /**
     *  Sets initial values for user if given.
     * 
     *  @param mixed $id the id of the user
     *  @param string $email the email of the user
     *  @param string $username the username of the user
     *  @param string $textmap the TextMap object for this user
     */
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

    /**
     *  Sets the map column 'was_used' to true or false accordingly in the database.
     * 
     *  @param int     $mapId id of the map to update
     *  @param boolean $used true or false if the map was used
     */
    public function updateMapWasUsed($mapId, $used)
    {
        $whereThisMap = (new Condition('TextMap'))->col('id')->equals($mapId);
        Database::UPDATE('TextMap', 'was_used', ($used ? 1 : 0), $whereThisMap);
    }

    /**
     *  Retrieves the TextMap for this user from the database..
     * 
     *  @param boolean $online true if this is being ran from an 
     *                         active user false if offline
     */
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
        
            if($online) $this->updateMapWasUsed($mapResult['row']['id'], false);
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

    /**
     *  Parses a thread from the given THread object using this user's map.
     * 
     *  @param Thread $thread Thread object to use to parse
     */
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

    /**
     *  Parses a post from the given Post object using this user's map.
     * 
     *  @param Post $post Post object to use to parse
     */
    public function parsePost($post)
    {
        if(!$post instanceof Post)
            CustomError::throw("Tried to pass non-Post 
                                object to parsePost().", 1);
        $map = $this->getValue('textmap');
        
        if($map instanceof TextMap) // For safety
        {
            $content = $post->getValue('content');

            $quoteLess = Formatting::stripQuoteTags($content);

            $map->parseSentences($quoteLess);
            $map->markAsParsedLater('posts', $post->getValue('id'));
        }
    }

    /**
     *  Generates a random thread under this user's name.
     */
    public function generateThread()
    {
        if($this->getValue('num_threads') < 3) return;
                
        $thread    = new Thread;
        $owner     = $this->getValue('id');
        $ownerName = $this->getValue('username');
        $map       = $this->getValue('textmap');
    
        $randomTitle = $map->generate(25, 40);
        $this->_pruneGeneratedTitle($randomTitle);
        
        $randomContent = $map->generate(rand(100, 500));
        $thread->setUpGeneratedThread($randomTitle, $randomContent, $owner, $ownerName);
        $threadResult = $thread->createThread();
    }

    /**
     *  Increments this user's number of posts in the database
     * 
     *  @param boolean $isRootPost if true then this post was actually a thread.
     */
    public function incrementNumPosts($isRootPost=false)
    {
        // UPDATE USER NUM_POST OR NUM_THREAD
        $whereThisUser = (new Condition('User'))->col('id')->equals($this->getValue('id'));
        $col = $isRootPost ? 'num_threads' : 'num_posts';
        $val = $this->getValue($col) + 1;
        $this->setValue($col, $val);
        Database::UPDATE('User', $col, $val, $whereThisUser);
    }

    /**
     *  Generates a random post under this user's name.
     */
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
            
            $size  = rand(10, 500);
            $extra = 1000 - $size; 
            $cap   = $size + rand(20, $extra);
            $randomContent;

            if(rand(0, 3))
            {
                $randomContent  = $this->_replyToRandomPost($threadId);
                $randomContent .= $map->generate($size, $cap);
            }
            else
            {
                $randomContent = $map->generate($size, $cap);
            }

            if(strlen($randomContent) >= $cap)
                $this->_pruneGeneratedTitle($randomContent);

            $post->setUpGeneratedPost($randomContent, $owner, $ownerName, $threadId);
            $postResult = $post->createPost();

            if($postResult instanceof Post)
            {
                $thread = new Thread;
                $thread->setValue('id', $threadId);
                $thread->setValue('replies', $threadRow['replies']);
                $thread->incrementReplies();
            }
        }
    }

    /**
     *  Saves this user's TextMap to the database, only if it wasnt alrdy saved
     *  offline by the server. Also marks all parsed threads/posts as parsed
     *  in the database so they cant be parsed again.
     * 
     *  @param boolean $online true if called from an active user's session
     */
    public function saveMap($online=true) // To be called in logout
    {
        $map = $this->getValue('textmap');
        $mapId = $map->getId();
        $whereThisMap = (new Condition('TextMap'))->col('id')->equals($mapId);
        $toBeMarked  = $map->getToMarkLater();
        $shouldParse = (count($toBeMarked['threads']) > 0 || count($toBeMarked['posts']) > 0);   
        
        if($shouldParse)
        {
            $loggingOutAndWasntUsed = $online && $this->_shouldUpdateContent($mapId, $whereThisMap); 
            
            if(!$online || $loggingOutAndWasntUsed)
            {
                $this->_updateParsedContent($toBeMarked);
                Database::UPDATE('TextMap', 'map_data', serialize($map), $whereThisMap);
            }
            
            if(!$online) $this->updateMapWasUsed($mapId, true);
        } 
    }

  //=========================================================//
 //                   PRIVATE FUNCTIONS                     //
//=========================================================//

    private function _replyToRandomPost($threadId)
    {
        $whereThisThread = (new Condition('Post'))->col('thread')->equals($threadId);
        $result = Database::SELECT(['content', 'owner_name'], 'Post', 
                                ['condition' => $whereThisThread]);

        $randomPost = rand(0, $result['num_rows'] - 1);
        $postRow    = $result['rows'][$randomPost];
        $content    = $postRow['content'];
        $author     = $postRow['owner_name'];
        return Formatting::addTags(Formatting::stripQuoteTags($content), $author);
    }

    private function _shouldUpdateContent($mapId, $whereThisMap)
    {
        $result = Database::SELECT('was_used', 'TextMap', 
                    ['fetch' => Database::ONE, 'condition' => $whereThisMap]);

        return $result['success'] && $result['num_rows'] > 0 && $result['row']['was_used'] == 0;
    }

    private function _updateParsedContent($toBeMarked)
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
    }

    private function _pruneGeneratedTitle(&$title)
    {
        $title  = trim($title);
        $chunks = preg_split('/(.*) [^ ]*$/i', $title, -1,  PREG_SPLIT_DELIM_CAPTURE);
        $title  = isset($chunks[0]) ? $chunks[0] : '';
        $title .= isset($chunks[1]) ? $chunks[1] : ''; 
        $title .= isset($chunks[2]) ? $chunks[2] : '';
    }
}