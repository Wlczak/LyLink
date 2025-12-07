<?php
declare (strict_types = 1);

namespace Lylink;

use Exception;
use Lylink\Auth\AuthSession;
use Lylink\Auth\DefaultAuth;
use Lylink\Data\EnvStore;
use Lylink\Interfaces\Datatypes\PlaybackInfo;
use Lylink\Mail\Mailer;
use Lylink\Models\Settings;
use Lylink\Models\User;
use Lylink\Routes\Integrations\Api\IntegrationApi;
use Lylink\Routes\Integrations\JellyfinIntegration;
use Lylink\Routes\Integrations\SpotifyIntegration;
use Lylink\Routes\LyricsRoute;
use Pecee\SimpleRouter\SimpleRouter;
use SpotifyWebAPI\Session;
use SpotifyWebAPI\SpotifyWebAPI;

class Router
{
    public static \Twig\Environment $twig;
    public static function handle(bool $devMode): void
    {
        $loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/../templates');
        self::$twig = new \Twig\Environment($loader, [
            'cache' => __DIR__ . '/../cache',
            'debug' => $devMode
        ]);

        ## User facing routes ##
        SimpleRouter::get('/', [self::class, 'home']);
        // SimpleRouter::redirect('/', $_ENV['BASE_DOMAIN'] . '/login', 307);

        ## Authenticated routes ##
        SimpleRouter::group(['middleware' => \Lylink\Middleware\AuthMiddleware::class], function () {
            SimpleRouter::partialGroup('/lyrics', LyricsRoute::setup());
            // SimpleRouter::get('/edit', [self::class, 'edit']);
            SimpleRouter::get('/settings', [self::class, 'settings']);
            SimpleRouter::partialGroup('/integrations', function () {
                SimpleRouter::partialGroup('/jellyfin', JellyfinIntegration::setup());
                SimpleRouter::partialGroup('/spotify', SpotifyIntegration::setup());
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
        // SimpleRouter::get('/callback', [self::class, 'spotify']);
        SimpleRouter::get('/ping', function () {
            return "pong";
        });
        SimpleRouter::get('/info', [self::class, 'info']);

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

        $code = random_int(100000, 999999);
        $_SESSION['email_verify'] = ['email' => $email, 'username' => $username, 'code' => $code, "exp" => time() + 30 * 60];

        $env = new EnvStore(stmp_host: $_ENV['SMTP_HOST'], stmp_username: $_ENV['SMTP_USERNAME'], stmp_password: $_ENV['SMTP_PASSWORD']);

        Mailer::prepareMail($email, $username, 'Email verification', self::$twig->load('email/verify_code.twig')->render(['code' => $code]), $env)->send();
        header('Location: ' . $_ENV['BASE_DOMAIN'] . '/email/verify');

        $data = $auth->register($email, $username, $pass, $passCheck);

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
            return self::$twig->load("verify.twig")->render(["success" => false, "errors" => $errors]);
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

    function info(): string
    {
        if (!isset($_SESSION['spotify_session'])) {
            header('Location: ' . $_ENV['BASE_DOMAIN'] . '/integrations/spotify/callback');
            die();
        }
        /**
         * @var Session|null
         */
        $session = $_SESSION['spotify_session'];

        if ($session == null) {
            header('Location: ' . $_ENV['BASE_DOMAIN'] . '/integrations/spotify/callback');
            die();
        }

        $api = new SpotifyWebAPI();
        $api->setAccessToken($session->getAccessToken());

        /**
         * @var PlaybackInfo|null
         */
        $info = $api->getMyCurrentPlaybackInfo();

        if ($info == null) {
            header('Location: ' . $_ENV['BASE_DOMAIN'] . '/integrations/spotify/callback');
            die();
        }
        if ($info->item == null) {
            header('Location: ' . $_ENV['BASE_DOMAIN'] . '/integrations/spotify/callback');
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
