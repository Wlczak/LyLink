<?php

namespace Tests\Auth;

use Lylink\Auth\AuthSession;
use Lylink\Auth\DefaultAuth;
use Lylink\Interfaces\Auth\Authorizator;
use PHPUnit\Framework\TestCase;

class AuthSessionTest extends TestCase
{
    protected function setUp(): void
    {
        session_start();
        session_regenerate_id();
    }

    protected function tearDown(): void
    {
        session_destroy();
    }

    public function testAuthGetWithSession(): void
    {
        $auth = new DefaultAuth();
        $_SESSION["auth"] = $auth;

        $this::assertInstanceOf(Authorizator::class, AuthSession::get());
        $this::assertSame($auth, AuthSession::get());
    }

    public function testAuthGetWithoutSession(): void
    {
        $this::assertNull(AuthSession::get());
    }

    public function testAuthGetWithOtherTypes(): void
    {
        $_SESSION["auth"] = "foo";
        $this::assertNull(AuthSession::get());
    }

    public function testAuthSet(): void
    {
        $auth = new DefaultAuth();
        AuthSession::set($auth);
        $this::assertSame($auth, AuthSession::get());
        $this::assertSame("local", $_SESSION["authType"]);
    }

    public function testAuthLogout(): void
    {
        $auth = new DefaultAuth();
        $_SESSION["auth"] = $auth;
        $auth->logout();
        $this::assertFalse($auth->isAuthorized());
        $this::assertNull($_SESSION["authType"]);
    }
}
