<?php

namespace Lylink\Routes\Integrations;

use Lylink\Auth\AuthSession;
use Lylink\Data\EnvStore;
use Lylink\Interfaces\Integration\IntegrationRoute;
use Lylink\Models\Settings;
use Lylink\Router;
use Lylink\Traits\IntegrationRoutingSetup;

class JellyfinIntegration extends Router implements IntegrationRoute
{
    use IntegrationRoutingSetup;

    public static function connect(): string
    {
        return self::$twig->load('integrations/jellyfin/connect_form.twig')->render();
    }

    public static function connectPost(): string
    {
        $username = $_POST["username"];
        $password = $_POST["password"];
        $lylinkAddress = $_POST["lylink_address"];

        return self::$twig->load('integrations/jellyfin/get_token.twig')->render(["address" => $lylinkAddress, "username" => $username, "password" => $password]);
    }

    public static function disconnect(): string
    {
        $env = EnvStore::load();
        $auth = AuthSession::get();
        if ($auth === null) {
            header('Location: ' . $env->BASE_DOMAIN . '/login');
            die();
        }

        $user = $auth->getUser();
        if ($user === null) {
            header('Location: ' . $env->BASE_DOMAIN . '/login');
            die();
        }

        $id = $user->getId();
        if ($id === null) {
            header('Location: ' . $env->BASE_DOMAIN . '/login');
            die();
        }

        $settings = Settings::getSettings($id);

        $settings->disconnectJellyfin();

        header('Location: ' . $env->BASE_DOMAIN . '/settings');

        return "";
    }
}
