<?php
declare (strict_types = 1);

namespace Lylink;

use Dotenv\Dotenv;
use Pecee\SimpleRouter\SimpleRouter;
use SpotifyWebAPI\Session;
use SpotifyWebAPI\SpotifyWebAPI;

class Router
{
    public static \Twig\Environment $twig;
    public static function handle(): void
    {
        $loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/../templates');
        self::$twig = new \Twig\Environment($loader, [
            'cache' => __DIR__ . '/../cache'
            , 'debug' => true
        ]);
        SimpleRouter::get('/', [self::class, 'home']);
        SimpleRouter::get('/callback', [self::class, 'login']);
        SimpleRouter::get('/lyrics', [self::class, 'lyrics']);
        SimpleRouter::get('/edit', [self::class, 'edit']);

        SimpleRouter::post('/edit/save', [self::class, 'update']);

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
            $entityManager = DoctrineRegistry::get();

            $lyrics = $entityManager->getRepository(Lyrics::class)->findOneBy(['spotify_id' => $id]);

            $template = self::$twig->load('lyrics.twig');
            var_dump($info->progress_ms / $info->item->duration_ms);

            $song = [
                'name' => $info->item->name,
                'artist' => $info->item->artists[0]->name,
                'duration' => $info->item->duration_ms / 1000,
                'duration_ms' => $info->item->duration_ms,
                'progress_ms' => $info->progress_ms,
                'imageUrl' => $info->item->album->images[0]->url,
                'id' => $info->item->id
            ];
            echo $template->render(
                [
                    'lyrics' => $lyrics->lyrics,
                    'song' => $song,
                    'progressPercent' => $info->progress_ms / $info->item->duration_ms * 100]
            );

        }
    }

    function edit(): void
    {
        /**
         * @var Session
         */
        $session = $_SESSION['spotify_session'];
        $trackId = $_GET['id'];

        $api = new SpotifyWebAPI();
        $api->setAccessToken($session->getAccessToken());

        /**
         * @var Object
         */
        $track = $api->getTrack($trackId);

        $template = self::$twig->load('edit.twig');

        $em = DoctrineRegistry::get();

        /**
         * @var Lyrics
         */
        $lyrics = $em->getRepository(Lyrics::class)->findOneBy(['spotify_id' => $trackId]);

        echo $template->render([
            'song' => [
                'name' => $track->name,
                'artist' => $track->artists[0]->name,
                'imageUrl' => $track->album->images[0]->url,
                'duration' => $track->duration_ms,
                'id' => $track->id
            ],
            'lyrics' => $lyrics->lyrics
        ]);
    }

    function update(): void
    {
        $entityManager = DoctrineRegistry::get();
        $lyrics = $entityManager->getRepository(Lyrics::class)->findOneBy(['spotify_id' => $_POST['id']]);
        if ($lyrics == null) {
            $lyrics = new Lyrics();
            $lyrics->spotify_id = $_POST['id'];
        }
        $lyrics->lyrics = $_POST['lyrics'];
        $entityManager->persist($lyrics);
        $entityManager->flush();
        header('Location: http://127.0.0.1:8080/lyrics');
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
