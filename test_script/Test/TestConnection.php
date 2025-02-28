<?php

include_once 'TestHelper.php';
include_once '../../config.php';

use Core\Database\Database;

global $dbObject;

try {
    $dbConfig = $config['db'];

    $dbObj = new Database('mysql');

    $dbObj->addConnection('default', $dbConfig['default']['development']);
    $dbObj->addConnection('slave', $dbConfig['slave']['development']);

    $dbObject = $dbObj;
} catch (Exception $e) {
    dd('TestConnection : Failed to connect to database.');
}

function db($conn = 'default')
{
    global $dbObject;
    return $dbObject->connect($conn);
}
