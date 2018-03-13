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

    // LOGIN ROUTE
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

    //  REGISTRATION ROUTE
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

    // THREADS ROUTE
$f3->route('GET /threads', function($f3)
{
    if(!loggedIn())
    {
        $f3->reroute('/login');
    }

    echo Template::instance()->render('views/threads.html');
});

    // POSTS ROUTE
$f3->route('GET /posts', function()
{
    if(!loggedIn())
    {
        $f3->reroute('/login');
    }

    echo Template::instance()->render('views/posts.html');
});

    // CREATE THREAD ROUTE
$f3->route('GET|POST /new-thread', function()
{
    if(!loggedIn())
    {
        $f3->reroute('/login');
    }
    
    echo Template::instance()->render('views/create_thread.html');
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
