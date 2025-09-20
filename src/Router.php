<?php
declare (strict_types = 1);

namespace Lylink;

use Dotenv\Dotenv;
use Lylink\Interfaces\Datatypes\PlaybackInfo;
use Lylink\Interfaces\Datatypes\Track;
use Lylink\Models\Lyrics;
use Lylink\Models\User;
use Pecee\SimpleRouter\SimpleRouter;
use SpotifyWebAPI\Session;
use SpotifyWebAPI\SpotifyWebAPI;
use SpotifyWebAPI\SpotifyWebAPIException;

class Router
{
    public static \Twig\Environment $twig;
    public static function handle(): void
    {
        $dotenv = Dotenv::createImmutable(__DIR__ . '/..');
        $dotenv->safeLoad();
        $loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/../templates');
        self::$twig = new \Twig\Environment($loader, [
            'cache' => __DIR__ . '/../cache',
            'debug' => true
        ]);

        ## User facing routes ##
        SimpleRouter::get('/', [self::class, 'home']);
        // SimpleRouter::redirect('/', $_ENV['BASE_DOMAIN'] . '/login', 307);

        SimpleRouter::group(['middleware' => \Lylink\Middleware\AuthMiddleware::class], function () {
            SimpleRouter::get('/lyrics', [self::class, 'lyrics']);
            SimpleRouter::get('/edit', [self::class, 'edit']);
        });

        SimpleRouter::get('/login', [self::class, 'login']);
        SimpleRouter::get('/register', [self::class, 'register']);
        SimpleRouter::post('/register', [self::class, 'registerPost']);

        ## Technical / api routes ##
        SimpleRouter::get('/callback', [self::class, 'spotify']);
        SimpleRouter::get('/ping', function () {
            return "pong";
        });
        SimpleRouter::get('/info', [self::class, 'info']);
        SimpleRouter::error(function ($request, $e) {
            http_response_code(404);

            if ($e->getMessage() == "Check settings on developer.spotify.com/dashboard, the user may not be registered.") {
                echo $template = self::$twig->load('whitelist.twig')->render();
                die();
            } else {
                throw $e;
            }
        });

        SimpleRouter::post('/edit/save', [self::class, 'update']);

        SimpleRouter::start();
    }

    public static function home(): string
    {
        return self::$twig->load('home.twig')->render();
    }

    public static function login(): string
    {
        return self::$twig->load('login.twig')->render();
    }

    public static function register(): string
    {
        return self::$twig->load('register.twig')->render();
    }

    public static function registerPost(): string
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $errors = [];

            $email = trim($_POST['email'] ?? '');
            $username = trim($_POST['username'] ?? '');
            $pass = $_POST['password'] ?? '';
            $passCheck = $_POST['password_confirm'] ?? '';

            if ($email === '' || $username === '' || $pass === '' || $passCheck === '') {
                $errors[] = 'All fields are required';
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Invalid email address';
            }

            if ($pass !== $passCheck) {
                $errors[] = 'Passwords do not match';
            }

            if (strlen($pass) < 8) {
                $errors[] = 'Password must be at least 8 characters long';
            }

            if (!preg_match('/[A-Z]/', $pass) || !preg_match('/[a-z]/', $pass) || !preg_match('/[0-9]/', $pass)) {
                $errors[] = 'Password must contain at least one uppercase letter one lowercase letter and one digit';
            }

            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            $em = DoctrineRegistry::get();
            $userRepo = $em->getRepository(User::class);

            if (empty($errors)) {
                $existingByEmail = $userRepo->findOneBy(['email' => $email]);
                if ($existingByEmail) {
                    $errors[] = 'Email is already registered';
                }

                $existingByUsername = $userRepo->findOneBy(['username' => $username]);
                if ($existingByUsername) {
                    $errors[] = 'Username is already taken';
                }
            }

            if (empty($errors)) {
                $hash = password_hash($pass, PASSWORD_BCRYPT);
                $user = new User($email, $username, $hash);

                try {
                    $em->persist($user);
                    $em->flush();
                    return self::$twig->load('register.twig')->render(['success' => true]);
                } catch (\Exception $e) {
                    $errors[] = 'Failed to create account';
                }
            }

            return self::$twig->load('register.twig')->render([
                'errors' => $errors,
                'old' => [
                    'email' => htmlspecialchars($email, ENT_QUOTES, 'UTF-8'),
                    'username' => htmlspecialchars($username, ENT_QUOTES, 'UTF-8')
                ]
            ]);
        }

        return self::$twig->load('register.twig')->render();

    }

    function lyrics(): void
    {
        if (!isset($_SESSION['spotify_session'])) {
            header('Location: ' . $_ENV['BASE_DOMAIN'] . '/callback');
        }

        /**
         * @var Session|null
         */
        $session = $_SESSION['spotify_session'];

        if ($session == null) {
            header('Location: ' . $_ENV['BASE_DOMAIN'] . '/callback');
            die();
        }

        if ($session->getTokenExpiration() < time()) {
            header('Location: ' . $_ENV['BASE_DOMAIN'] . '/callback');
            die();
        }

        $api = new SpotifyWebAPI();
        $api->setAccessToken($session->getAccessToken());

        try {
            /**
             * @var PlaybackInfo|null
             */
            $info = $api->getMyCurrentPlaybackInfo();
        } catch (SpotifyWebAPIException $e) {
            if ($e->getMessage() == "Check settings on developer.spotify.com/dashboard, the user may not be registered.") {
                echo $template = self::$twig->load('whitelist.twig')->render();
                die();
            } else {
                throw $e;
            }
        }
        if ($info == null) {
            $song = [
                'name' => "No song is currently playing",
                'artist' => "",
                'duration' => 0,
                'duration_ms' => 0,
                'progress_ms' => 0,
                'imageUrl' => $_ENV['BASE_DOMAIN'] . '/img/albumPlaceholer.svg',
                'id' => 0,
                'is_playing' => "false"
            ];

            echo $template = self::$twig->load('lyrics.twig')->render([
                'song' => $song
            ]);

        } else {

            if ($info->item == null) {
                die();
            }

            $id = $info->item->id;

            //echo $id;
            $entityManager = DoctrineRegistry::get();

            /**
             * @var Lyrics|null
             */
            $lyrics = $entityManager->getRepository(Lyrics::class)->findOneBy(['spotify_id' => $id]);

            if ($lyrics == null) {
                $lyrics = new Lyrics();
            }

            $template = self::$twig->load('lyrics.twig');

            $song = [
                'name' => $info->item->name,
                'artist' => $info->item->artists[0]->name,
                'duration' => $info->item->duration_ms / 1000,
                'duration_ms' => $info->item->duration_ms,
                'progress_ms' => $info->progress_ms,
                'imageUrl' => $info->item->album->images[0]->url,
                'id' => $info->item->id,
                'is_playing' => $info->is_playing
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
         * @var Track
         */
        $track = $api->getTrack($trackId);

        $template = self::$twig->load('edit.twig');

        $em = DoctrineRegistry::get();

        /**
         * @var Lyrics|null
         */
        $lyrics = $em->getRepository(Lyrics::class)->findOneBy(['spotify_id' => $trackId]);
        if ($lyrics == null) {
            $lyrics = new Lyrics();
        }

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
        header('Location: ' . $_ENV['BASE_DOMAIN'] . '/lyrics');
    }

    function spotify(): string
    {
        if (isset($_SESSION['spotify_session'])) {
            /**
             * @var Session
             */
            $session = $_SESSION['spotify_session'];
            $session->refreshAccessToken();
            header('Location: ' . $_ENV['BASE_DOMAIN'] . '/lyrics');
        }

        if (!isset($_SESSION['spotify_session'])) {
            $clientID = $_ENV['CLIENT_ID'];
            $clientSecret = $_ENV['CLIENT_SECRET'];

            $session = new Session(
                $clientID,
                $clientSecret,
                $_ENV['BASE_DOMAIN'] . '/callback'
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

                header('Location: ' . $_ENV['BASE_DOMAIN'] . '/lyrics');

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

    function info(): string
    {
        if (!isset($_SESSION['spotify_session'])) {
            header('Location: ' . $_ENV['BASE_DOMAIN'] . '/callback');
            die();
        }
        /**
         * @var Session|null
         */
        $session = $_SESSION['spotify_session'];

        if ($session == null) {
            header('Location: ' . $_ENV['BASE_DOMAIN'] . '/callback');
            die();
        }

        $api = new SpotifyWebAPI();
        $api->setAccessToken($session->getAccessToken());

        /**
         * @var PlaybackInfo|null
         */
        $info = $api->getMyCurrentPlaybackInfo();

        if ($info == null) {
            header('Location: ' . $_ENV['BASE_DOMAIN'] . '/callback');
            die();
        }
        if ($info->item == null) {
            header('Location: ' . $_ENV['BASE_DOMAIN'] . '/callback');
            die();
        }

        $song = [
            'name' => $info->item->name,
            'artist' => $info->item->artists[0]->name,
            'duration' => $info->item->duration_ms / 1000,
            'duration_ms' => $info->item->duration_ms,
            'progress_ms' => $info->progress_ms,
            'imageUrl' => $info->item->album->images[0]->url,
            'id' => $info->item->id,
            'is_playing' => $info->is_playing
        ];

        return json_encode($song) ?: "{}";
    }
}
