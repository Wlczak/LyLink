<?php

namespace Lylink\Routes\Integrations;

use Lylink\Interfaces\Integration\IntegrationRoute;
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
        return self::$twig->load('integrations/jellyfin/disconnect.twig')->render(['test' => 'test']);
    }
}
