<?php

namespace Tests\Models;

use Lylink\DoctrineRegistry;
use Lylink\Models\Settings;
use Lylink\Models\User;
use PHPUnit\Framework\TestCase;
use Tests\Helpers\TestDatabaseHelper;

class SettingsTest extends TestCase
{
    protected function setUp(): void
    {
        TestDatabaseHelper::createTestDatabase();
    }

    protected function tearDown(): void
    {
        TestDatabaseHelper::dropTestDatabase();
    }

    public function testConstructorAndGetters(): void
    {
        $settings = new Settings(42);

        $this::assertNull($settings->getId());
        $this::assertSame(42, $settings->getUserId());
    }

    public function testGetSettingsCreatesRecordWhenMissing(): void
    {
        $settings = Settings::getSettings(7);

        $this::assertInstanceOf(Settings::class, $settings);
        $this::assertSame(7, $settings->getUserId());
    }

    public function testSpotifyConnectionLifecycle(): void
    {
        $settings = Settings::getSettings(8);
        $settings->connectSpotify("token", "user");

        $this::assertTrue($settings->spotify_connected);
        $this::assertSame("token", $settings->spotify_token);
        $this::assertSame("user", $settings->spotify_user_display_name);

        $settings->disconnectSpotify();

        $this::assertFalse($settings->spotify_connected);
        $this::assertFalse($settings->allow_spotify_login);
        $this::assertNull($settings->spotify_user_id);
        $this::assertNull($settings->spotify_user_display_name);
        $this::assertNull($settings->spotify_token);
    }

    public function testJellyfinConnectionLifecycle(): void
    {
        $settings = Settings::getSettings(9);
        $settings->allow_jellyfin_login = true;
        $settings->jellyfin_connected = true;
        $settings->jellyfin_server = "http://jellyfin.test";
        $settings->jellyfin_user_id = "user";
        $settings->jellyfin_token = "token";

        $settings->disconnectJellyfin();

        $this::assertFalse($settings->allow_jellyfin_login);
        $this::assertFalse($settings->jellyfin_connected);
        $this::assertNull($settings->jellyfin_server);
        $this::assertNull($settings->jellyfin_user_id);
        $this::assertNull($settings->jellyfin_token);
    }

    public function testSettingsPersistThroughDoctrineRegistry(): void
    {
        $user = new User("settings@test.test", "settingsuser", password_hash("Aa123456", PASSWORD_BCRYPT));
        DoctrineRegistry::get()->persist($user);
        DoctrineRegistry::get()->flush();

        $settings = Settings::getSettings($user->getId() ?? 0);
        $this::assertSame($user->getId(), $settings->getUserId());
    }
}
