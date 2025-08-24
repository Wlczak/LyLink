<?php

require_once __DIR__ . '/bootstrap.php';

use Lylink\Router;

session_start();

try{
Router::handle();
}catch (Exception $e){
    echo $e;
}