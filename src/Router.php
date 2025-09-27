<?php
declare (strict_types = 1);

namespace Lylink;

use Dotenv\Dotenv;
use Exception;
use Lylink\Auth\AuthSession;
use Lylink\Auth\DefaultAuth;
use Lylink\Interfaces\Datatypes\PlaybackInfo;
use Lylink\Interfaces\Datatypes\Track;
use Lylink\Mail\Mailer;
use Lylink\Models\Lyrics;
use Lylink\Models\Settings;
use Lylink\Models\User;
use Lylink\Routes\Integrations\Api\IntegrationApi;
use Lylink\Routes\Integrations\Jellyfin;
use Lylink\Routes\LyricsRoute;
use Pecee\SimpleRouter\SimpleRouter;
use SensitiveParameter;
use SpotifyWebAPI\Session;
use SpotifyWebAPI\SpotifyWebAPI;

class Router
{
    public static \Twig\Environment $twig;
    public static function handle(): void
    {
        $dotenv = Dotenv::createImmutable(__DIR__ . '/../config');
        $dotenv->safeLoad();
        $loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/../templates');
        self::$twig = new \Twig\Environment($loader, [
            'cache' => __DIR__ . '/../cache',
            'debug' => true
        ]);

        ## User facing routes ##
        SimpleRouter::get('/', [self::class, 'home']);
        // SimpleRouter::redirect('/', $_ENV['BASE_DOMAIN'] . '/login', 307);

        ## Authenticated routes ##
        SimpleRouter::group(['middleware' => \Lylink\Middleware\AuthMiddleware::class], function () {
            SimpleRouter::partialGroup('/lyrics', LyricsRoute::setup());
            SimpleRouter::get('/edit', [self::class, 'edit']);
            SimpleRouter::get('/settings', [self::class, 'settings']);
            SimpleRouter::partialGroup('/integrations', function () {
                SimpleRouter::partialGroup('/jellyfin', Jellyfin::setup());
                SimpleRouter::partialGroup('/api', IntegrationApi::setup());
            });
        });

        SimpleRouter::get('/login', [self::class, 'login']);
        SimpleRouter::post('/login', [self::class, 'loginPost']);
        SimpleRouter::get('/register', [self::class, 'register']);
        SimpleRouter::post('/register', [self::class, 'registerPost']);
        SimpleRouter::get('/email/verify', [self::class, 'emailVerify']);
        SimpleRouter::post('/email/verify', [self::class, 'emailVerifyPost']);
        SimpleRouter::get('/logout', function () {
            AuthSession::logout();
            header('Location: ' . $_ENV['BASE_DOMAIN']);
        });

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
        return self::$twig->load('home.twig')->render([
            'auth' => AuthSession::get()
        ]);
    }

    public static function login(): string
    {
        return self::$twig->load('login.twig')->render();
    }

    public static function loginPost(): string
    {
        $username = trim($_POST['username'] ?? '');
        $pass = $_POST['password'] ?? '';

        $auth = new DefaultAuth();
        $data = $auth->login($username, $pass);

        AuthSession::set($auth);

        return self::$twig->load('login.twig')->render($data);
    }

    public static function register(): string
    {
        return self::$twig->load('register.twig')->render();
    }

    public static function registerPost(): string
    {
        $email = trim($_POST['email'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $pass = $_POST['password'] ?? '';
        $passCheck = $_POST['password_confirm'] ?? '';

        $auth = new DefaultAuth();
        $data = $auth->register($email, $username, $pass, $passCheck);

        if ($data['success']) {
            $code = random_int(100000, 999999);
            $_SESSION['email_verify'] = ['email' => $email, 'username' => $username, 'code' => $code, "exp" => time() + 30 * 60];

            Mailer::send($email, $username, 'Email verification', self::$twig->load('email/verify_code.twig')->render(['code' => $code]));
            header('Location: ' . $_ENV['BASE_DOMAIN'] . '/email/verify');
        }

        return self::$twig->load('register.twig')->render($data);
    }

    function emailVerify(): string
    {
        if (!isset($_SESSION['email_verify'])) {
            header('Location: ' . $_ENV['BASE_DOMAIN']);
            die();
        }
        /**
         * @var array{email:string,username:string,code:int,exp:int}
         */
        $verify = $_SESSION['email_verify'];
        return self::$twig->load('verify.twig')->render(["email" => $verify["email"]]);
    }

    function emailVerifyPost(): string
    {
        if (!isset($_SESSION['email_verify'])) {
            header('Location: ' . $_ENV['BASE_DOMAIN']);
            die();
        }
        /**
         * @var array{email:string,username:string,code:int,exp:int}
         */
        $verify = $_SESSION['email_verify'];

        /**
         * @var int|null
         */
        $code = $_POST['code'];

        if ($code == null) {
            header('Location: ' . $_ENV['BASE_DOMAIN']);
            die();
        }

        if ($verify['code'] == $_POST['code']) {
            $em = DoctrineRegistry::get();
            /**
             * @var User|null
             */
            $user = $em->getRepository(User::class)->findOneBy(['email' => $verify['email']]);

            if ($user == null) {
                header('Location: ' . $_ENV['BASE_DOMAIN']);
                die();
            }

            $user->verifyEmail();

            unset($_SESSION['email_verify']);
            // header('Location: ' . $_ENV['BASE_DOMAIN']);
            return self::$twig->load("verify.twig")->render(["success" => true]);
        } else {
            $errors = ["Incorrect verification code"];
            return self::$twig->load("verify.twig")->render(["success" => false,"errors"=>$errors]);
        }

    }

    function settings(): string
    {
        $auth = AuthSession::get();
        if ($auth == null) {
            header('Location: ' . $_ENV['BASE_DOMAIN'] . '/login');
            die();
        }
        $user = $auth->getUser();
        if ($user == null) {
            header('Location: ' . $_ENV['BASE_DOMAIN'] . '/login');
            die();
        }
        $id = $user->getId();
        if ($id === null) {
            throw new Exception("invalid user id");
        }
        return self::$twig->load('settings.twig')->render(['user' => $user, 'settings' => Settings::getSettings($id)]);
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

        $template = self::$twig->load('lyrics/edit.twig');

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
        header('Location: ' . $_ENV['BASE_DOMAIN'] . '/lyrics/spotify');
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
