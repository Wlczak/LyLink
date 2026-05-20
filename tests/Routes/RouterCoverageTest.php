<?php

namespace Tests\Routes;

use Lylink\Auth\AuthSession;
use Lylink\Auth\DefaultAuth;
use Lylink\Router;
use Lylink\DoctrineRegistry;
use Lylink\Models\Settings;
use Lylink\Models\User;
use PHPUnit\Framework\TestCase;
use Tests\Helpers\TestDatabaseHelper;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

class RouterCoverageTest extends TestCase
{
    protected function setUp(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $_SESSION = [];
        $_POST = [];
        $_GET = [];
        $_ENV['SMTP_HOST'] = 'smtp.test.test';
        $_ENV['SMTP_USERNAME'] = 'test@test.test';
        $_ENV['SMTP_PASSWORD'] = 'secret';
        $_ENV['CLIENT_ID'] = 'client';
        $_ENV['CLIENT_SECRET'] = 'secret';
        $_ENV['BASE_DOMAIN'] = 'base.test';
        TestDatabaseHelper::createTestDatabase();
        Router::$twig = new Environment(new ArrayLoader([
            'home.twig' => 'home {{ auth is not null ? "auth" : "guest" }}',
            'login.twig' => 'login {{ success|default(false) ? "ok" : "fail" }} {{ usermail|default("") }} {{ errors|default([])|join(",") }}',
            'register.twig' => 'register {{ success|default(false) ? "ok" : "fail" }} {{ errors|default([])|join(",") }}',
            'verify.twig' => 'verify {{ email|default("") }} {{ success|default(false) ? "ok" : "fail" }} {{ errors|default([])|join(",") }}',
            'settings.twig' => 'settings {{ user.username }} {{ settings.userId }}',
            'email/verify_code.twig' => 'code {{ code }}',
        ]));
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        $_POST = [];
        $_GET = [];
        session_destroy();
        TestDatabaseHelper::dropTestDatabase();
    }

    public function testHomeLoginAndRegisterViews(): void
    {
        $this::assertStringContainsString('guest', Router::home());
        $this::assertSame('login fail  ', Router::login());
        $this::assertSame('register fail ', Router::register());
    }

    public function testLoginPostSuccessAndFailure(): void
    {
        $password = 'Aa123456';
        TestDatabaseHelper::queryDatabase('INSERT INTO users (email, password, username, emailVerified) VALUES ("router@test.test", "' . password_hash($password, PASSWORD_BCRYPT) . '", "router", 1)');

        $_POST = ['username' => 'router', 'password' => $password];
        $result = Router::loginPost();

        $this::assertStringContainsString('login ok router', $result);
        $auth = AuthSession::get();
        $this::assertInstanceOf(DefaultAuth::class, $auth);
        $this::assertTrue($auth?->isAuthorized() ?? false);

        $_SESSION = [];
        $_POST = ['username' => 'router', 'password' => 'wrong'];
        $result = Router::loginPost();
        $this::assertStringContainsString('Invalid password', $result);
    }

    public function testRegisterPostFailureAndEmailVerifyFlow(): void
    {
        $_POST = [
            'email' => 'invalid',
            'username' => '',
            'password' => 'short',
            'password_confirm' => 'diff',
        ];

        $result = Router::registerPost();
        $this::assertStringContainsString('Invalid email address', $result);

        $user = new User('verify@test.test', 'verify', password_hash('Aa123456', PASSWORD_BCRYPT));
        DoctrineRegistry::get()->persist($user);
        DoctrineRegistry::get()->flush();

        $_SESSION['email_verify'] = [
            'email' => 'verify@test.test',
            'username' => 'verify',
            'code' => 123456,
            'exp' => time() + 60,
        ];
        $_POST = ['code' => '123456'];

        $router = new Router();
        $verifyResult = $router->emailVerifyPost();

        $this::assertStringContainsString('verify ', $verifyResult);
        $this::assertStringContainsString('ok', $verifyResult);
        $this::assertNull($_SESSION['email_verify'] ?? null);

        $verifiedUser = DoctrineRegistry::get()->getRepository(User::class)->findOneBy(['email' => 'verify@test.test']);
        $this::assertTrue($verifiedUser?->isEmailVerified() ?? false);
    }

    public function testSettingsAndInfo(): void
    {
        $user = new User('settings@test.test', 'settings', password_hash('Aa123456', PASSWORD_BCRYPT));
        DoctrineRegistry::get()->persist($user);
        DoctrineRegistry::get()->flush();

        $settings = Settings::getSettings($user->getId() ?? 0);
        $settings->allow_edit = true;
        $settings->spotify_connected = true;
        $settings->jellyfin_connected = true;

        $auth = new DefaultAuth();
        $auth->login('settings@test.test', 'Aa123456');
        AuthSession::set($auth);

        $router = new Router();
        $this::assertStringContainsString('settings settings', $router->settings());

        $session = new \SpotifyWebAPI\Session();
        $_SESSION['spotify_session'] = $session;
        \SpotifyWebAPI\SpotifyWebAPI::$nextPlaybackInfo = $this->makePlaybackInfo();
        $json = $router->info();

        $this::assertStringContainsString('"id":"track-1"', $json);
        $this::assertStringContainsString('"is_playing":true', $json);
    }

    private function makePlaybackInfo(): object
    {
        return (object) [
            'item' => (object) [
                'id' => 'track-1',
                'name' => 'Track One',
                'duration_ms' => 180000,
                'artists' => [(object) ['name' => 'Artist']],
                'album' => (object) [
                    'images' => [(object) ['url' => 'https://img.test/1']],
                ],
            ],
            'progress_ms' => 45000,
            'is_playing' => true,
        ];
    }
}
