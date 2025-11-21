<?php

namespace Lylink\Routes\Integrations;

use Closure;
use Lylink\Interfaces\Integration\IntegrationRoute;
use Lylink\Router;
use Lylink\Traits\IntegrationRoutingSetup;
use Pecee\SimpleRouter\SimpleRouter;
use SpotifyWebAPI\Session;
use SpotifyWebAPI\SpotifyWebAPI;

class SpotifyIntegration extends Router implements IntegrationRoute
{
    use IntegrationRoutingSetup;

    public static function setup(): Closure
    {
        return function () {
            SimpleRouter::get('/callback', [self::class, 'callback']);
            IntegrationRoutingSetup::setup();
        };
    }

    public static function callback(): string
    {
        if (isset($_SESSION['spotify_session'])) {
            /**
             * @var Session
             */
            $session = $_SESSION['spotify_session'];
            $session->refreshAccessToken();
            header('Location: ' . $_ENV['BASE_DOMAIN'] . '/lyrics/spotify');
        }

        if (!isset($_SESSION['spotify_session'])) {
            $clientID = $_ENV['CLIENT_ID'];
            $clientSecret = $_ENV['CLIENT_SECRET'];

            $session = new Session(
                $clientID,
                $clientSecret,
                $_ENV['BASE_DOMAIN'] . '/integrations/spotify/callback'
            );

            if (!isset($_GET['code'])) {
                $options = [
                    'scope' => ['user-read-currently-playing', "user-read-playback-state"]
                ];

                header('Location: ' . $session->getAuthorizeUrl($options));
                die();
            }

            if ($session->requestAccessToken($_GET['code'])) {

                $_SESSION['spotify_session'] = $session;

                header('Location: ' . $_ENV['BASE_DOMAIN'] . '/lyrics/spotify');

                return "";

            } else {
                return "";
            }
        } else {
            /**
             * @var Session
             */
            $session = $_SESSION['spotify_session'];
            $api = new SpotifyWebAPI();
            $api->setAccessToken($session->getAccessToken());
            $api->me();
        }

        $api = new SpotifyWebAPI();

        return "";

    }

    public static function connect(): string
    {
        throw new \Exception('Not implemented');
    }

    public static function connectPost(): string
    {
        throw new \Exception('Not implemented');
    }

    public static function disconnect(): string
    {
        throw new \Exception('Not implemented');
    }
}
