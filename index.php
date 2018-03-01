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
$f3->route('GET|POST /', function()
{
    echo Template::instance()->render('views/home.html');
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

$f3->run();


    //  End Runtime clock
require_once "testing/runTimeEnd.php";
