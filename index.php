<?php
/**
 *  Jake Landowski
 *  Shahbaz Iqbal 
 *  
 *  2 - 8 - 18
 *  
 *  Route controller for Fauxram assignment.
*/  
//test
  //================================================//
 //                     SETUP                      //
//================================================//

error_reporting(E_ALL);
ini_set('display_errors', 1);

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

    //  HOME ROUTE
$f3->route('GET /', function()
{
    echo Template::instance()->render('views/home.html');
});
    //THREAD ROUTE
$f3->route('GET /thread', function()
{
    echo Template::instance()->render('views/thread.html');
});

$f3->run();
