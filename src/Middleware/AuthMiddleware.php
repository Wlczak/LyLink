<?php

namespace Lylink\Middleware;

use Lylink\Auth\AuthSession;
use Lylink\Data\EnvStore;
use Pecee\Http\Middleware\IMiddleware;
use Pecee\Http\Request;

class AuthMiddleware implements IMiddleware
{

    public function handle(Request $request): void
    {
        $env = EnvStore::load();
        $auth = AuthSession::get();
        if ($auth == null) {
            header('Location: ' . $env->BASE_DOMAIN . '/login');
            return;
        }

        if (!$auth->isAuthorized()) {
            header('Location: ' . $env->BASE_DOMAIN . '/login');
            return;
        }
        return;
    }
}
