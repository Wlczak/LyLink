<?php

require_once __DIR__ . '/bootstrap.php';

use Lylink\Router;

session_start();

Router::handle();
