<?php

spl_autoload_register(function($className)
{
    require_once getenv('HOME') . "/328/fauxrum/model/classes/{$className}.php";
});

$result = Database::SELECT_ALL('User');

print_r($result);