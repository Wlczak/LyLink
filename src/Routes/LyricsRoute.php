<?php

namespace Lylink\Routes;

use Closure;
use Lylink\Auth\AuthSession;
use Lylink\Interfaces\Routing\Route;
use Lylink\Router;
use Pecee\SimpleRouter\SimpleRouter;

class LyricsRoute extends Router implements Route
{
    public static function setup(): Closure
    {
        return function () {
            SimpleRouter::get('/', [self::class, 'lyricsHome']);
            SimpleRouter::get('/spotify', [self::class, 'lyrics']);
            SimpleRouter::post('/spotify', [self::class, 'update']);
        };
    }

    public static function lyricsHome(): string
    {
        $auth = AuthSession::get();
        if ($auth == null) {
            header('Location: ' . $_ENV['BASE_DOMAIN'] . '/login');
            die();
        }
        return self::$twig->load('lyrics/lyrics_page.twig')->render(["auth" => $auth]);
    }
}
