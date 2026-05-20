<?php

namespace Tests\Auth;

use Lylink\DoctrineRegistry;
use Lylink\Auth\DefaultAuth;
use Lylink\Models\Settings;
use Lylink\Models\User;
use PHPUnit\Framework\TestCase;
use Tests\Helpers\TestDatabaseHelper;

class DefaultAuthTest extends TestCase
{
    private string $email = "test@test.test";
    private string $password = "test";
    private string $username = "test";

    protected function setUp(): void
    {
        TestDatabaseHelper::createTestDatabase();
    }

    protected function tearDown(): void
    {
        TestDatabaseHelper::dropTestDatabase();
    }

    public function testLoginWithoutAccount(): void
    {
        $auth = new DefaultAuth();

        $email = "test@test.test";
        $result = $auth->login($email, "test");

        $this::assertFalse($result["success"]);
        $this::assertSame($email, $result["usermail"]);
        $this::assertSame("User not found", $result["errors"][0]);
    }

    public function testLoginWithAccountWithEmail(): void
    {
        $auth = new DefaultAuth();

        $passwordHash = password_hash($this->password, PASSWORD_BCRYPT);

        $this::assertIsArray(TestDatabaseHelper::queryDatabase('INSERT INTO users (email, password, username, emailVerified) VALUES ("' . $this->email . '", "' . $passwordHash . '", "' . $this->username . '", 1)'));

        $result = $auth->login($this->email, $this->password);

        $this::assertTrue($result["success"]);
        $this::assertSame($this->email, $result["usermail"]);
        $this::assertSame([], $result["errors"]);
    }

    public function testLoginWithAccountWithUsername(): void
    {
        $auth = new DefaultAuth();

        $passwordHash = password_hash($this->password, PASSWORD_BCRYPT);

        $this::assertIsArray(TestDatabaseHelper::queryDatabase('INSERT INTO users (email, password, username, emailVerified) VALUES ("' . $this->email . '", "' . $passwordHash . '", "' . $this->username . '", 1)'));

        $result = $auth->login($this->username, $this->password);

        $this::assertTrue($result["success"]);
        $this::assertSame($this->username, $result["usermail"]);
        $this::assertSame([], $result["errors"]);
    }

    public function testRegisterSuccess(): void
    {
        $auth = new DefaultAuth();

        $result = $auth->register(
            $this->email,
            $this->username,
            "Aa123456",
            "Aa123456"
        );

        $this::assertTrue($result["success"]);
        $this::assertSame([], $result["errors"]);
        $this::assertSame($this->email, $result["old"]["email"]);
        $this::assertSame($this->username, $result["old"]["username"]);

        $user = DoctrineRegistry::get()->getRepository(User::class)->findOneBy(["email" => $this->email]);
        $this::assertInstanceOf(User::class, $user);
        $this::assertTrue($user?->isEmailVerified() === false);

        $settings = DoctrineRegistry::get()->getRepository(Settings::class)->findOneBy(["user_id" => $user?->getId()]);
        $this::assertInstanceOf(Settings::class, $settings);
    }

    public function testRegisterRejectsInvalidInput(): void
    {
        $auth = new DefaultAuth();

        $result = $auth->register(
            "not-an-email",
            "",
            "short",
            "different"
        );

        $this::assertFalse($result["success"]);
        $this::assertContains("All fields are required", $result["errors"]);
        $this::assertContains("Invalid email address", $result["errors"]);
        $this::assertContains("Passwords do not match", $result["errors"]);
        $this::assertContains("Password must be at least 8 characters long", $result["errors"]);
        $this::assertContains("Password must contain at least one uppercase letter one lowercase letter and one digit", $result["errors"]);
    }
}
