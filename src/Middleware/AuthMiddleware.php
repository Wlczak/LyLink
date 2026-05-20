<?php

use Lylink\Auth\AuthSession;
use Lylink\Data\EnvStore;
use Pecee\Http\Middleware\IMiddleware;
use Pecee\Http\Request;

class AuthMiddleware implements IMiddleware
{

    public function handle(Request $request): void
    {
        $auth = AuthSession::get();
        if ($auth === null || !$auth->isAuthorized()) {
            try {
                $env = EnvStore::load();
                header('Location: ' . $env->BASE_DOMAIN . '/login');
            } catch (\Throwable $e) {
                return;
            }
            return;
        }

        return;
    }
}
