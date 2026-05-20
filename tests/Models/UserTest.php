<?php

namespace Tests\Models;

use Lylink\DoctrineRegistry;
use Lylink\Models\Settings;
use Lylink\Models\User;
use PHPUnit\Framework\TestCase;
use Tests\Helpers\TestDatabaseHelper;

class UserTest extends TestCase
{
    protected function setUp(): void
    {
        TestDatabaseHelper::createTestDatabase();
    }

    protected function tearDown(): void
    {
        TestDatabaseHelper::dropTestDatabase();
    }

    public function testConstructorAndAccessors(): void
    {
        $user = new User("user@test.test", "username", password_hash("Aa123456", PASSWORD_BCRYPT));

        $this::assertNull($user->getId());
        $this::assertSame("user@test.test", $user->getEmail());
        $this::assertFalse($user->isEmailVerified());
        $this::assertSame("username", (string) $user);
        $this::assertTrue($user->checkPassword("Aa123456"));
        $this::assertFalse($user->checkPassword("wrong"));
    }

    public function testVerifyEmailPersistsState(): void
    {
        $user = new User("verify@test.test", "verify", password_hash("Aa123456", PASSWORD_BCRYPT));
        DoctrineRegistry::get()->persist($user);
        DoctrineRegistry::get()->flush();

        $user->verifyEmail();

        $this::assertTrue($user->isEmailVerified());
    }

    public function testUpdateJellyfinUpdatesSettings(): void
    {
        $user = new User("jellyfin@test.test", "jellyfin", password_hash("Aa123456", PASSWORD_BCRYPT));
        DoctrineRegistry::get()->persist($user);
        DoctrineRegistry::get()->flush();

        $settings = Settings::getSettings($user->getId() ?? 0);
        $user->updateJellyfin("http://jellyfin.example", "token", true);

        $this::assertTrue($settings->jellyfin_connected);
        $this::assertTrue($settings->allow_jellyfin_login);
        $this::assertSame("http://jellyfin.example", $settings->jellyfin_server);
        $this::assertSame("token", $settings->jellyfin_token);
        $this::assertNull($settings->jellyfin_user_id);
    }
}
