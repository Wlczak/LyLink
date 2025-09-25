<?php

require_once __DIR__ . '/../bootstrap.php';

use Lylink\Router;

session_start();
session_regenerate_id();

if (isset($_SESSION['email_verify'])) {
    if (time() > $_SESSION['email_verify']['exp']) {
        unset($_SESSION['email_verify']);
    }
}

try {
    Router::handle();
} catch (Exception $e) {
    echo $e;
}
