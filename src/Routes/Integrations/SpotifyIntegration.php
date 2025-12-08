<?php

namespace Lylink\Routes\Integrations;

use Closure;
use Lylink\Auth\AuthSession;
use Lylink\Interfaces\Integration\IntegrationRoute;
use Lylink\Models\Settings;
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
            self::traitSetup()();
            SimpleRouter::get('/callback', [self::class, 'callback']);
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

            $settings = Settings::getSettings(AuthSession::get()?->getUser()?->getId() ?? 0);
            $token = $session->getAccessToken();

            $session = $_SESSION['spotify_session'];
            $api = new SpotifyWebAPI();
            $api->setAccessToken($session->getAccessToken());
            /**
             * @var array{display_name:string}
             */
            $spotifyUser = (array)$api->me();

            $spotifyUsername = $spotifyUser['display_name'];
            $settings->connectSpotify($token, $spotifyUsername);

            header('Location: ' . $_ENV['BASE_DOMAIN'] . '/settings');
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

                $settings = Settings::getSettings(AuthSession::get()?->getUser()?->getId() ?? 0);
                $token = $session->getAccessToken();

                $session = $_SESSION['spotify_session'];
                $api = new SpotifyWebAPI();
                $api->setAccessToken($session->getAccessToken());
                /**
                 * @var array{display_name:string}
                 */
                $spotifyUser = $api->me();

                $spotifyUsername = $spotifyUser['display_name'];

                $settings->connectSpotify($token, $spotifyUsername);

                header('Location: ' . $_ENV['BASE_DOMAIN'] . '/settings');

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
        header('Location: ' . $_ENV['BASE_DOMAIN'] . '/integrations/spotify/callback');
        return "";
    }

    public static function connectPost(): string
    {
        throw new \Exception('Not implemented');
    }

    public static function disconnect(): string
    {
        $settings = Settings::getSettings(AuthSession::get()?->getUser()?->getId() ?? 0);
        $settings->disconnectSpotify();
        header('Location: ' . $_ENV['BASE_DOMAIN'] . '/settings');
        return "";
    }
}
