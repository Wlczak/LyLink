<?php

require_once __DIR__ . '/../bootstrap.php';

use Dotenv\Dotenv;
use Lylink\Router;

session_start();
session_regenerate_id();

if (isset($_SESSION['email_verify'])) {
    if (time() > $_SESSION['email_verify']['exp']) {
        unset($_SESSION['email_verify']);
    }
}

$dotenv = Dotenv::createImmutable(__DIR__ . '/../config');
$dotenv->safeLoad();

$devMode = $_ENV['DEV_MODE'] === "true" ? true : false;

if ($devMode) {
} else {
    error_reporting(E_ALL & ~E_DEPRECATED);
}

try {
    ob_start();
    Router::handle($devMode);
    echo ob_get_clean();
} catch (Exception $e) {
    ob_clean();
    http_response_code(500);
    try {
        $loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/../templates');
        $twig = new \Twig\Environment($loader, [
            'cache' => __DIR__ . '/../cache',
            'debug' => $devMode
        ]);
        if ($devMode) {
            echo $twig->load('error.twig')->render(['message' => $e->getMessage(), "code" => $e->getCode(), "line" => $e->getLine(), "file" => $e->getFile(), "trace" => $e->getTrace()]);
        } else {
            echo $twig->load('error.twig')->render(['message' => "Something has gone very wrong", "code" => $e->getCode()]);
        }

    } catch (Exception $e) {
        echo "Something has gone very wrong";
        if ($devMode) {
            throw $e;
        }
    }
}
