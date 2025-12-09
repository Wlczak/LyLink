<?php

namespace Tests\Auth;

use Lylink\Auth\DefaultAuth;
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
        $this::assertSame($this->email, $result["usermail"]);
        $this::assertSame([], $result["errors"]);
    }
}
