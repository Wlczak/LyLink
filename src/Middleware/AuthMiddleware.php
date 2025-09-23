<?php

namespace Lylink\Middleware;

use Lylink\Auth\AuthSession;
use Pecee\Http\Middleware\IMiddleware;
use Pecee\Http\Request;

class AuthMiddleware implements IMiddleware
{

    public function handle(Request $request): void
    {
        $auth = AuthSession::get();
        if ($auth == null) {
            header('Location: ' . $_ENV['BASE_DOMAIN'] . '/login');
            return;
        }

        if (!$auth->isAuthorized()) {
            header('Location: ' . $_ENV['BASE_DOMAIN'] . '/login');
            return;
        }
        return;
    }
}
