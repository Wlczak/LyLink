<?php

namespace Lylink\Auth;

use Lylink\Auth\DefaultAuth;
use Lylink\Interfaces\Auth\Authorizator;

class AuthSession
{
    public static function get(): ?Authorizator
    {
        if (isset($_SESSION['auth'])) {
            /**
             * @var Authorizator|mixed
             */

            $auth = $_SESSION['auth'];
            if ($auth instanceof Authorizator) {
                return $auth;
            }
        }
        return null;
    }

    public static function set(Authorizator $auth): void
    {
        $_SESSION['auth'] = $auth;
        switch (get_class($auth)) {
            case DefaultAuth::class:
                $_SESSION['authType'] = 'local';
                break;
        }
    }

    public static function logout(): void
    {
        $auth = self::get();
        if ($auth instanceof Authorizator) {
            $auth->logout();
        }
        unset($_SESSION['auth']);
        unset($_SESSION['authType']);
    }
}
