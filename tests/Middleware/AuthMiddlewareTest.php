<?php

namespace Tests\Middleware;

use AuthMiddleware;
use Lylink\Auth\DefaultAuth;
use PHPUnit\Framework\TestCase;

class AuthMiddlewareTest extends TestCase
{
    public static function setUpAuth(): void
    {
        $auth = new DefaultAuth();
        $_SESSION["auth"] = $auth;
    }

    public function testAuthMiddlewareHandle(): void
    {
        $middleware = new AuthMiddleware();
        $middleware->handle(new \Pecee\Http\Request());
    }
}
