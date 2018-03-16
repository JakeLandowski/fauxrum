<?php
/**
 *  Jake Landowski
 *  Shahbaz Iqbal
 *  2-8-18
 *  
 *  Route controller for Fauxrum assignment.
*/  

  //================================================//
 //                     SETUP                      //
//================================================//

error_reporting(E_ALL|E_STRICT);
ini_set('display_errors', 1);

    //  Start Runtime clock
require_once "testing/runTimeStart.php";

require_once 'vendor/autoload.php';
session_start();

$f3 = Base::instance();
$f3->set('DEBUG', 3);

  //================================================//
 //                   PRE-ROUTE                    //
//================================================//

function isPost()
{
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

function loggedIn()
{
    return isset($_SESSION['User']);
}

function errorIfTokenInvalid($f3, $token, $tokenChecker)
{
    if($tokenChecker($token)) $f3->error(404);
}
    // Custom 404 Page
// $f3->set('ONERROR', function($f3)
// {
//     echo Template::instance()->render('views/404.html');
// });

  //================================================//
 //                    ROUTES                      //
//================================================//

    // HOME ROUTE
$f3->route('GET /', function($f3)
{
    if(loggedIn())
    {
        $f3->reroute('/threads');
    }
    else
    {
        $f3->reroute('/login');
    }
});

    // LOGOUT ROUTE
$f3->route('GET /logout', function($f3)
{
    if(loggedIn())
    {
        if(isset($_COOKIE[session_name()]))
        {
            setcookie(session_name(), '', time() - 3600, '/' );
        } 
        $_SESSION = array();
        session_destroy();       
    }

    $f3->reroute('/login');
});

  //=========================================================//
 //                  LOGIN/REGISTER ROUTES                  //
//=========================================================//

$f3->route('GET|POST /login', function($f3)
{
    if(loggedIn())
    {
        $f3->reroute('/threads');
    }
    else if(isPost())
    {
        $login = new Login;
        $login->validate();
        
        if(count($login->getErrors()) == 0)
        {
            $loginResult = $login->logUserIn();
            
            if($loginResult instanceof User)
            {
                    // success, save in session and reroute
                $_SESSION['User'] = $loginResult;
                $f3->reroute('/threads');
            }
            else
            {
                // failed insert error message to print to user
                $f3->set('fail_message', $loginResult);
            }
        }
    
        $f3->mset([
            'errors'    => $login->getErrors(),
            'email'     => $login->displayValue('email'),
            'username'  => $login->displayValue('username')
        ]);
    }

    echo Template::instance()->render('views/login.html');
});

$f3->route('GET|POST /register', function($f3)
{
    if(loggedIn())
    {
        $f3->reroute('/threads');
    }
    else if(isPost())
    {
        $registration = new Registration;
        $registration->validate();
        
        if(count($registration->getErrors()) == 0)
        {
            $registerResult = $registration->registerUser();
            
            if($registerResult instanceof User)
            {
                    // success, save in session and reroute
                $_SESSION['User'] = $registerResult;
                $f3->reroute('/threads');
            }
            else
            {
                // failed insert error message to print to user
                $f3->set('fail_message', $registerResult);
            }
        }
    
        $f3->mset([
            'errors'    => $registration->getErrors(),
            'email'     => $registration->displayValue('email'),
            'username'  => $registration->displayValue('username')
        ]);
    }

    echo Template::instance()->render('views/register.html');
});

  //=========================================================//
 //                     LIST ALL ROUTES                     //
//=========================================================//

$f3->route('GET /threads', function($f3)
{
    if(!loggedIn())
    {
        $f3->reroute('/login');
    }

    $threads = Thread::getThreads(); 
    
    if(is_array($threads)) // Success
    {
        $f3->set('user_id', $_SESSION['User']->displayValue('id'));
        $f3->set('threads', $threads);
    }
    else // Fail
    {
        $f3->set('fail_message', $threads);
    }

    echo Template::instance()->render('views/threads.html');
});

$f3->route('GET /posts/@thread_id', function($f3, $params)
{
    if(!loggedIn())
    {
        $f3->reroute('/login');
    }

    errorIfTokenInvalid($f3, $params['thread_id'], function($token)
    {
        return !is_numeric($token) || (int)$token < 1;
    });
    
    $threadId = (int) $params['thread_id'];
    $userId   = $_SESSION['User']->displayValue('id');
    
    $posts = Post::getPosts($threadId);
    $thread = Thread::getThread($threadId);
    
    if(is_array($posts)) // Success
    {
        if($thread instanceof Thread) // Success
        {
            $thread->incrementViews($userId);
            $f3->set('user_id', $userId);
            $f3->set('thread', $thread);
            $f3->set('posts', $posts);
        }
        else // Fail
        {
            $f3->set('fail_message', $thread);    
        }
    }
    else // Fail
    {
        $f3->set('fail_message', $posts);
    }

    echo Template::instance()->render('views/posts.html');
});

  //=========================================================//
 //                   NEW/CREATE ROUTES                     //
//=========================================================//

$f3->route('GET|POST /new-thread', function($f3)
{
    if(!loggedIn())
    {
        $f3->reroute('/login');
    }
    else if(isPost())
    {
        $user = $_SESSION['User'];
        $thread = new Thread;
        $thread->setValue('owner', $user->getValue('id'));
        
        $thread->validate();

        if(count($thread->getErrors()) == 0)
        {
            $threadResult = $thread->createThread();
            
            if($threadResult instanceof Thread)
            {
                $threadId = $thread->displayValue('id');
                $f3->reroute("/posts/$threadId");
            }
            else
            {
                    // failed insert, error message to print to user
                $f3->set('fail_message', $threadResult);
            }
        }
    
        $f3->mset([
            'errors'  => $thread->getErrors(),
            'title'   => $thread->displayValue('title'),
            'content' => $thread->getValue('root_post')->displayValue('content')
        ]);
    }
    
    echo Template::instance()->render('views/new_thread.html');
});

$f3->route('GET|POST /new-post/@thread_id/@post_id', function($f3, $params)
{
    if(!loggedIn())
    {
        $f3->reroute('/login');
    }
    
    errorIfTokenInvalid($f3, $params['thread_id'], function($token)
    {
        return !is_numeric($token) || (int)$token < 1;
    });

    errorIfTokenInvalid($f3, $params['post_id'], function($token)
    {
        return !is_numeric($token) || (int)$token < 1;
    });

    $replyingInThreadId = (int) $params['thread_id'];
    $repliedToPostId    = (int) $params['post_id'];

    $f3->set('thread_id', $replyingInThreadId);
    $f3->set('post_id', $repliedToPostId);

    if(isPost())
    {
        $user = $_SESSION['User'];
        $post = new Post;
        $post->setValue('owner', $user->getValue('id'));
        $post->setValue('thread', $replyingInThreadId);
        
        $post->validate();

        if(count($post->getErrors()) == 0)
        {
            $postResult = $post->createPost();
            
            if($postResult instanceof Post)
            {
                $thread = Thread::getThread($replyingInThreadId);

                if($thread instanceof Thread) // Success
                {
                    $thread->incrementReplies();
                }
                    // success, show the post
                $f3->reroute("/posts/$replyingInThreadId"); 
            }
            else
            {
                    // failed insert, error message to print to user
                $f3->set('fail_message', $postResult);
            }
        }
    
        $f3->mset([
            'errors'  => $post->getErrors(),
            'content' => $post->displayValue('content'),
        ]);
    }
    
    echo Template::instance()->render('views/new_post.html');
});

  //=========================================================//
 //                      EDIT ROUTES                        //
//=========================================================//

$f3->route('GET|POST /edit-thread/@thread_id', function($f3, $params)
{
    if(!loggedIn())
    {
        $f3->reroute('/login');
    }

    errorIfTokenInvalid($f3, $params['thread_id'], function($token)
    {
        return !is_numeric($token) || (int)$token < 1;
    });

    $userId = $_SESSION['User']->displayValue('id');
    $threadId = (int) $params['thread_id'];
    $thread = Thread::getThread($threadId);
    
    if($thread instanceof Thread) // Success
    {
        if($userId == $thread->getValue('owner'))
        {
            $f3->set('thread', $thread);
            
            if(isPost())
            {
                $newThread = new Thread;
                $newThread->validateTitle();
                
                if(count($newThread->getErrors()) == 0)
                {
                    $thread->editTitle($newThread->getValue('title'));
                    $f3->reroute("/posts/$threadId");
                }
                else 
                {
                    $f3->set('errors', $newThread->getErrors());
                    $f3->set('title',  $newThread->displayValue('title'));
                }
            }
        }
        else
        {
            $f3->set('fail_message', 'You are not the owner of this thread');    
        }
    }
    else // Fail
    {
        $f3->set('fail_message', $thread);    
    }

    echo Template::instance()->render('views/edit_thread.html');
});

$f3->route('GET|POST /edit-post/@post_id', function($f3, $params)
{
    if(!loggedIn())
    {
        $f3->reroute('/login');
    }

    errorIfTokenInvalid($f3, $params['post_id'], function($token)
    {
        return !is_numeric($token) || (int)$token < 1;
    });

    $userId = $_SESSION['User']->displayValue('id');
    $postId   = (int) $params['post_id'];
    $post     = Post::getPost($postId);
    
    if($post instanceof Post) // Success
    {
        if($userId == $post->getValue('owner'))
        {
            $f3->set('post', $post);
            
            if(isPost())
            {
                $threadId = $post->getValue('thread');
                $newPost = new Post;
                $newPost->validate();
                
                if(count($newPost->getErrors()) == 0)
                {
                    $post->editContent($newPost->getValue('content'));
                    $f3->reroute("/posts/$threadId");
                }
                else 
                {
                    $f3->set('errors', $newPost->getErrors());
                    $f3->set('content', $newPost->displayValue('content'));
                }
            }
        }
        else
        {
            $f3->set('fail_message', 'You are not the owner of this post');    
        }
    }
    else // Fail
    {
        $f3->set('fail_message', $post);    
    }

    echo Template::instance()->render('views/edit_post.html');
});

  //=========================================================//
 //                     DELETE ROUTES                       //
//=========================================================//

$f3->route('GET|POST /delete-post/@thread_id/@post_id', function($f3, $params)
{
    if(!loggedIn())
    {
        $f3->reroute('/login');
    }

    errorIfTokenInvalid($f3, $params['post_id'], function($token)
    {
        return !is_numeric($token) || (int)$token < 1;
    });

    errorIfTokenInvalid($f3, $params['thread_id'], function($token)
    {
        return !is_numeric($token) || (int)$token < 1;
    });

    $userId = $_SESSION['User']->displayValue('id');
    $postId   = (int) $params['post_id'];
    $threadId = (int) $params['thread_id'];
    $post     = Post::getPost($postId); 
    $returnRoute = "/posts/$threadId";
    
    if($post instanceof Post) // Success 
    {
        if($userId == $post->getValue('owner'))
        {
            $f3->set('post', $post);
            
            if(isPost())
            {
                if($post->deletePost())
                {
                    $f3->reroute($returnRoute);
                }
                else
                {
                    $f3->set('fail_message', 'Sorry, failed to delete post');        
                }
            }
        }
        else
        {
            $f3->set('fail_message', 'You are not the owner of this post');    
        }
    }
    else // Fail
    {
        $f3->set('fail_message', $post);    
    }

    $f3->mset([
        'route'        => "/delete-post/$threadId/$postId", 
        'return_route' => $returnRoute,
        'message'      => 'Are you sure you want to delete this post?',
        // 'post'         => $post
    ]);

    echo Template::instance()->render('views/confirmation.html');
});

$f3->route('GET|POST /delete-thread/@thread_id', function($f3, $params)
{
    if(!loggedIn())
    {
        $f3->reroute('/login');
    }

    errorIfTokenInvalid($f3, $params['thread_id'], function($token)
    {
        return !is_numeric($token) || (int)$token < 1;
    });

    $userId = $_SESSION['User']->displayValue('id');
    $threadId = (int) $params['thread_id'];
    $thread   = Thread::getThread($threadId); 
    $returnRoute = "/threads";
    
    if($thread instanceof Thread) // Success 
    {
        if($userId == $thread->getValue('owner'))
        {
            $f3->set('thread', $thread);
            
            if(isPost())
            {
                if($thread->deleteThread())
                {
                    $f3->reroute($returnRoute);
                }
                else
                {
                    $f3->set('fail_message', 'Sorry, failed to delete thread');        
                }
            }
        }
        else
        {
            $f3->set('fail_message', 'You are not the owner of this thread');    
        }
    }
    else // Fail
    {
        $f3->set('fail_message', $thread);    
    }

    $f3->mset([
        'route'        => "/delete-thread/$threadId", 
        'return_route' => $returnRoute,
        'message'      => 'Are you sure you want to delete this thread?',
        // 'thread'         => $thread
    ]);

    echo Template::instance()->render('views/confirmation.html');
});

  //================================================//
 //                    TESTING                     //
//================================================//

$f3->route('GET|POST /test', function()
{
    spl_autoload_register(function($className)
    {
        require_once "./testing/{$className}.php";
    });

    echo '<pre style="color:white;">';

    echo '</pre>';

    echo Template::instance()->render('testing/db_testing.html');
});

  //================================================//
 //                    TESTING                     //
//================================================//


$f3->run();

    //  End Runtime clock
require_once "testing/runTimeEnd.php";
