<?php

require_once __DIR__ . '/../bootstrap.php';

use Dotenv\Dotenv;
use Lylink\Router;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;

session_start();
session_regenerate_id();

if (isset($_SESSION['email_verify'])) {
    if (time() > $_SESSION['email_verify']['exp']) {
        unset($_SESSION['email_verify']);
    }
}

$dotenv = Dotenv::createImmutable(__DIR__ . '/../config');
$dotenv->safeLoad();

$devMode = $_ENV['DEV_MODE'] === "true"  ?true : false;

if ($devMode) {
} else {
    error_reporting(E_ALL & ~E_DEPRECATED);

    set_error_handler(function ($severity, $message, $file, $line) {
        if (!(error_reporting() & $severity)) {
            return false;
        }
        throw new ErrorException($message, 0, $severity, $file, $line);
    });
}

if (!$devMode) {
    ini_set('display_errors', '0');
    register_shutdown_function(function () {
        $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_CORE_WARNING, E_COMPILE_ERROR, E_COMPILE_WARNING];
        $err = error_get_last();
        if ($err && in_array($err['type'], $fatalTypes, true)) {
            if (ob_get_level()) {
                while (ob_get_level()) {
                    ob_end_clean();
                }
            }
            http_response_code(500);
            $ray = uniqid();

            if (class_exists(\Monolog\Logger::class)) {
                try {
                    $log = new \Monolog\Logger('shutdown');
                    $log->pushHandler(new \Monolog\Handler\StreamHandler(__DIR__ . '/../logs/logs.log', \Monolog\Level::Error));
                    $log->error($err['message'], ['ray' => $ray, 'file' => $err['file'], 'line' => $err['line'], 'type' => $err['type']]);
                } catch (\Throwable $t) {
                    error_log("[$ray] " . $err['message'] . " in " . $err['file'] . ":" . $err['line']);
                }
            } else {
                error_log("[$ray] " . $err['message'] . " in " . $err['file'] . ":" . $err['line']);
            }

            if (class_exists(\Twig\Environment::class)) {
                try {
                    $loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/../templates');
                    $twig = new \Twig\Environment($loader, ['cache' => __DIR__ . '/../cache', 'debug' => false]);
                    echo $twig->render('error.twig', ['message' => 'Oops, something went wrong.', 'ray' => $ray]);
                    return;
                } catch (\Throwable $t) {
                }
            }

            echo 'Oops, something went wrong.';
        }
    });
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

        $errorRay = uniqid();

        $log = new Logger('mainLogger');
        $log->pushHandler(new StreamHandler('../logs/logs.log', Level::Info));
        $log->addRecord(Level::Error, $e->getMessage(), ['ray' => $errorRay, 'code' => $e->getCode(), 'message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine(), 'trace' => $e->getTraceAsString()]);

        if ($e->getMessage() == "Check settings on developer.spotify.com/dashboard, the user may not be registered.") {
            echo $template = self::$twig->load('whitelist.twig')->render();
            die();
        }

        if ($devMode) {
            echo $twig->load('error.twig')->render(['message' => $e->getMessage(), "code" => $e->getCode(), "line" => $e->getLine(), "file" => $e->getFile(), "trace" => $e->getTrace(), 'ray' => $errorRay]);
        } else {
            echo $twig->load('error.twig')->render(['message' => "Oops, something has gone wrong...", "code" => $e->getCode(), 'ray' => $errorRay]);
        }

    } catch (Exception $e) {
        echo "Something has gone very wrong";
        if ($devMode) {
            throw $e;
        }
    }
}

// SimpleRouter::error(function ($request, $e) {

//     if ($e->getMessage() == "Check settings on developer.spotify.com/dashboard, the user may not be registered.") {
//         echo $template = self::$twig->load('whitelist.twig')->render();
//         die();
//     } else {
//         throw $e;
//     }
// });
