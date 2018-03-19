<?php

require_once 'runTimeStart.php';

spl_autoload_register(function($className)
{
    if(file_exists(getenv('HOME') . "/328/fauxrum/model/classes/{$className}.php"))
        require_once getenv('HOME') . "/328/fauxrum/model/classes/{$className}.php";
    else if(file_exists(getenv('HOME') . "/328/fauxrum/views/classes/{$className}.php"))
        require_once getenv('HOME') . "/328/fauxrum/views/classes/{$className}.php";
});

$whereEnoughContent = (new Condition('User'))->col('num_threads')->greaterThan(2)
                      ->or()->col('num_posts')->greaterThan(2);
                      
$result = Database::SELECT(['id', 'username', 'num_threads', 'num_posts'], 'User', ['condition' => $whereEnoughContent]);

function successful($result)
{
    return isset($result['success'])  && $result['success'] && 
           isset($result['num_rows']) && $result['num_rows'] > 0 && 
           isset($result['rows']);
}

function getUnparsed($thing, $column, $user)
{
    $thingNotParsed = (new Condition($thing))->col('parsed')->equals(0); 
    $whereThisUser  = (new Condition($thing))->col('owner')->equals($user);
    return Database::SELECT([$column, 'id'], $thing, 
             ['condition' => $whereThisUser->and($thingNotParsed)]);
}

if(successful($result))
{
    $rows = $result['rows'];
    $user;
    $userId;
    $threadResults;
    $postResults;
    $threadRows;
    $postRows;
    $thread;
    $post;
    $numberOfThreads;
    $numberOfPosts;

    foreach($rows as $row)
    {
        $userId = $row['id'];
        $user = new User($userId, null, $row['username']);
        $user->setValue('num_threads',  $row['num_threads']);
        $user->setValue('num_posts',    $row['num_posts']);
        $user->fetchMapFromDatabase(true); 
        // passing true will update Map's was_used to true
        // to indicate this offline script used the Map 
        
        $threadResults = getUnparsed('Thread', 'title', $userId);

        if(successful($threadResults))
        {
            $threadRows = $threadResults['rows'];
            foreach($threadRows as $row)
            {
                $thread = new Thread;
                $thread->setValue('id',    $row['id']);
                $thread->setValue('title', $row['title']);
                $user->parseThread($thread);
            }
        }

        $postResults = getUnparsed('Post', 'content', $userId);

        if(successful($postResults))
        {
            $postRows = $postResults['rows'];
            foreach($postRows as $row)
            {
                $post = new Post;
                $post->setValue('id',      $row['id']);
                $post->setValue('content', $row['content']);
                $user->parsePost($post);
            }
        }

        if(rand(0, 1) == 1)
        {
            $numberOfThreads = rand(1, 3);
            
            for($i = 0; $i < $numberOfThreads; $i++)
            {
                $user->generateThread();
                echo 'generated thread';   
            }
        }

        if(rand(0, 1) == 1)
        {
            $numberOfPosts = rand(1, 5);

            for($i = 0; $i < $numberOfPosts; $i++)
            {
                $user->generatePost();
                echo 'generated post';
            }
        }

        $user->saveMap(false); // false to indicate offline
    }
}





// $ids = [2, 3, 4, 5, 6, 1];
// $args = [];
// $count = count($ids);

// for($i = 0; $i < $count; $i++)
// {
//     $args[] = '?';
// }

// $args = implode(',', $args);

// $sql = "SELECT id, username FROM User WHERE id IN($args)";

// $connection = Database::connect();

// try
// {
//     $statement = $connection->prepare($sql);

//     foreach($ids as $i => $id)
//     {
//         $statement->bindValue($i + 1,  $id,  PDO::PARAM_INT);
//     }

//     if($statement->execute())
//     {
//         $result = $statement->fetchAll(PDO::FETCH_ASSOC);
        
//         echo '<pre>';
//         print_r($result);
//         echo '</pre>';

        // $statement->rowCount();

        // $getTotalRows = 'SELECT found_rows() AS totalRows';
        // $connection->query($getTotalRows)->fetch(PDO::FETCH_ASSOC);
//     }

//     Database::disconnect($connection);
// }
// catch(PDOException $e)
// {
//     Database::disconnect($connection);
//     CustomError::throw('Query failed: ' . $e->getMessage());
// }


require_once 'runTimeEnd.php';