<?php
declare (strict_types = 1);

namespace Lylink;

use Dotenv\Dotenv;
use Pecee\SimpleRouter\SimpleRouter;
use SpotifyWebAPI\Session;
use SpotifyWebAPI\SpotifyWebAPI;

class Router
{
    public static function handle(): void
    {
        SimpleRouter::get('/', [self::class, 'home']);
        SimpleRouter::get('/callback', [self::class, 'login']);
        SimpleRouter::get('/lyrics', [self::class, 'lyrics']);

        SimpleRouter::start();
    }

    public static function home(): string
    {
        if (!isset($_SESSION['spotify_session'])) {
            return "not logged in";
        } else {
            return "logged in";
        }
    }

    function lyrics(): void
    {
        if (!isset($_SESSION['spotify_session'])) {
            header('Location: http://127.0.0.1:8080/callback');
        }

        /**
         * @var Session
         */
        $session = $_SESSION['spotify_session'];

        $api = new SpotifyWebAPI();
        $api->setAccessToken($session->getAccessToken());

        /**
         * @var Object
         */
        $info = $api->getMyCurrentPlaybackInfo();

        /**
         * @var string
         */
        $id = $info->item->id;

        if ($id == null) {
            echo "no song is currently playing";
        } else {
            echo $id;
        }
    }

    function login(): string
    {

        if (!isset($_SESSION['spotify_session'])) {

            $dotenv = Dotenv::createImmutable(__DIR__ . '/..');
            $dotenv->safeLoad();
            $clientID = $_ENV['CLIENT_ID'];
            $clientSecret = $_ENV['CLIENT_SECRET'];

            $session = new Session(
                $clientID,
                $clientSecret,
                'http://127.0.0.1:8080/callback'
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

                header('Location: http://127.0.0.1:8080/lyrics');

                return "nice";

            } else {
                return "frick";
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
}
