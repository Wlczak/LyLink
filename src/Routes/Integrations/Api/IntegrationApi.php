<?php

namespace Lylink\Routes\Integrations\Api;

use Closure;
use Lylink\Auth\AuthSession;
use Pecee\SimpleRouter\SimpleRouter;

class IntegrationApi
{
    public static function setup(): Closure
    {
        return function () {
            SimpleRouter::post('/jellyfin', [self::class, 'addJellyfin']);
        };
    }

    public static function addJellyfin(): string
    {
        $input = file_get_contents('php://input');
        if ($input === false || $input === '') {
            http_response_code(400);
            return '';
        }

        /**
         * @var array{address:string,token:string}
         */
        $json = json_decode($input, true);

        $auth = AuthSession::get();

        if ($auth === null) {
            http_response_code(401);
            return '';
        }

        if (!$auth->isAuthorized()) {
            http_response_code(401);
            return '';
        }

        $user = $auth->getUser();

        if ($user === null) {
            http_response_code(401);
            return '';
        }

        $user->updateJellyfin($json['address'], $json['token'], true);

        return json_encode(["success" => true]) !== false ? json_encode(["success" => true]) : '';
    }
}
