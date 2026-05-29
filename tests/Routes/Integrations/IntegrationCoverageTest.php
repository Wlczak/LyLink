<?php

namespace Tests\Routes\Integrations;

use Lylink\Auth\AuthSession;
use Lylink\Auth\DefaultAuth;
use Lylink\DoctrineRegistry;
use Lylink\Models\Settings;
use Lylink\Models\User;
use Lylink\Router;
use Lylink\Routes\Integrations\JellyfinIntegration;
use Lylink\Routes\Integrations\SpotifyIntegration;
use PHPUnit\Framework\TestCase;
use Tests\Helpers\TestDatabaseHelper;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

class IntegrationCoverageTest extends TestCase
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
            'integrations/jellyfin/connect_form.twig' => 'jelly-connect',
            'integrations/jellyfin/get_token.twig' => 'jelly-token {{ address }} {{ username }} {{ password }}',
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

    public function testJellyfinConnectAndPostAndDisconnect(): void
    {
        $auth = $this->createAuthorizedUser('jellyfin@test.test', 'jellyfin');
        $settings = Settings::getSettings($auth->getUser()?->getId() ?? 0);
        $settings->jellyfin_connected = true;
        $settings->allow_jellyfin_login = true;
        $settings->jellyfin_server = 'http://jellyfin.test';
        $settings->jellyfin_token = 'token';

        $this::assertSame('jelly-connect', JellyfinIntegration::connect());

        $_POST = [
            'username' => 'user',
            'password' => 'pass',
            'lylink_address' => 'http://lylink.test',
        ];
        $this::assertStringContainsString('jelly-token http://lylink.test user pass', JellyfinIntegration::connectPost());

        JellyfinIntegration::disconnect();
        $freshSettings = Settings::getSettings($auth->getUser()?->getId() ?? 0);
        $this::assertFalse($freshSettings->jellyfin_connected);
        $this::assertFalse($freshSettings->allow_jellyfin_login);
        $this::assertNull($freshSettings->jellyfin_server);
        $this::assertNull($freshSettings->jellyfin_token);
    }

    public function testSpotifyCallbackConnectDisconnectAndConnectPost(): void
    {
        $auth = $this->createAuthorizedUser('spotify@test.test', 'spotify');
        $settings = Settings::getSettings($auth->getUser()?->getId() ?? 0);
        $settings->spotify_connected = false;

        $session = new \SpotifyWebAPI\Session();
        $_SESSION['spotify_session'] = $session;
        \SpotifyWebAPI\SpotifyWebAPI::$nextMe = ['display_name' => 'Spotify Display'];
        $this::assertSame('', SpotifyIntegration::callback());

        $freshSettings = Settings::getSettings($auth->getUser()?->getId() ?? 0);
        $this::assertTrue($freshSettings->spotify_connected);
        $this::assertSame('Spotify Display', $freshSettings->spotify_user_display_name);

        $this::assertSame('', SpotifyIntegration::connect());

        $this::expectException(\Exception::class);
        SpotifyIntegration::connectPost();
    }

    private function createAuthorizedUser(string $email, string $username): DefaultAuth
    {
        $password = 'Aa123456';
        $user = new User($email, $username, password_hash($password, PASSWORD_BCRYPT));
        DoctrineRegistry::get()->persist($user);
        DoctrineRegistry::get()->flush();

        $auth = new DefaultAuth();
        $auth->login($email, $password);
        AuthSession::set($auth);
        return $auth;
    }
}
