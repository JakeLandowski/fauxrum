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

  //================================================//
 //                    ROUTES                      //
//================================================//

// HOME ROUTE
$f3->route('GET /', function($f3)
{
    $f3->reroute('/login');
});

    // HOME ROUTE
$f3->route('GET|POST /login', function()
{
    echo Template::instance()->render('views/login.html');
});

    // THREADS ROUTE
$f3->route('GET /threads', function()
{
    echo Template::instance()->render('views/threads.html');
});

    // POSTS ROUTE
$f3->route('GET /posts', function()
{
    echo Template::instance()->render('views/posts.html');
});

    // CREATE THREAD ROUTE
$f3->route('GET /new-thread', function()
{
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

    $registration = new Registration;

    $registration->validate();
    print_r($registration->getErrors());
    // echo $registration->displayValue('email');
    $registerResult = $registration->registerUser(); 
    if($registerResult)
    {
        if($registerResult['success'])
        {
            if($registerResult['num_rows'] > 0)
            {
                // create user object and redirect
                $id = $registerResult['id'];
            }

        }
        else 
        {
            
        }
    }
    else // still errors
    {

    }

    echo Template::instance()->render('testing/db_testing.html');
});

  //================================================//
 //                    TESTING                     //
//================================================//


$f3->run();

    //  End Runtime clock
require_once "testing/runTimeEnd.php";
