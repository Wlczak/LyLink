<?php

namespace Lylink\Routes\Integrations;

use Lylink\Auth\AuthSession;
use Lylink\Interfaces\Integration\IntegrationRoute;
use Lylink\Models\Settings;
use Lylink\Traits\IntegrationSetup;

class Jellyfin extends \Lylink\Router implements IntegrationRoute
{
    use IntegrationSetup;

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
        $auth = AuthSession::get();
        if ($auth === null) {
            header('Location: ' . $_ENV['BASE_DOMAIN'] . '/login');
            die();
        }

        $user = $auth->getUser();
        if ($user === null) {
            header('Location: ' . $_ENV['BASE_DOMAIN'] . '/login');
            die();
        }

        $id = $user->getId();
        if ($id === null) {
            header('Location: ' . $_ENV['BASE_DOMAIN'] . '/login');
            die();
        }

        $settings = Settings::getSettings($id);

        $settings->disconnectJellyfin();

        header('Location: ' . $_ENV['BASE_DOMAIN'] . '/settings');

        return "";
    }
}
