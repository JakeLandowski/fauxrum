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

  //================================================//
 //                    TESTING                     //
//================================================//

$f3->route('GET /test', function()
{
    spl_autoload_register(function($className)
    {
        require_once "./testing/{$className}.php";
    });

    $cond = (new Condition)->col('id')->greaterThan('int', 1)->and()->col('name')->equals('string', 'bob');
    $cond2 = (new Condition)->col('id')->greaterThan('int', 1)->and()->col('name')->equals('string', 'bob')->and($cond);

    echo $cond2;
    echo '<pre style="color:white;">';
    print_r($cond2->getBindsAndValues());
    echo '</pre>';

    // echo $cond2;

    foreach($cond2->getValues() as $value)
    {
        echo '<br>Value: ';
        print_r($value);
    }

    foreach($cond2->getBinds() as $bind)
    {
        echo '<br>Bind: ';
        print_r($bind);
    }
    
    // print_r(Database::SELECT('id', 'User', '', Database::ONE));
    // print_r(Database::SELECT_ALL('Use', 'id = 1'));

    echo Template::instance()->render('testing/db_testing.html');
});

  //================================================//
 //                    TESTING                     //
//================================================//


$f3->run();

    //  End Runtime clock
require_once "testing/runTimeEnd.php";
