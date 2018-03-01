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

// ~~~~ BIG ERROR REPORTING ~~~~ //
        ini_set('display_errors', 0);
        // Deprecated directives
        @ini_set('magic_quotes_gpc', 0);
        @ini_set('register_globals', 0);

        // Abort on startup error
        // Intercept errors/exceptions; PHP5.3-compatible

        set_exception_handler(function($obj) use($f3)
        {
            $f3->error(500,$obj->getmessage(),$obj->gettrace());
        });

        set_error_handler(function($code,$text) use($f3)
        {
            if (error_reporting())
            {
                $f3->error(500,$text);
            }
        });
// ~~~~ BIG ERROR REPORTING ~~~~ //


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
