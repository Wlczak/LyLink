<?php

namespace Tests\Auth;

use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use Lylink\DoctrineRegistry;
use Lylink\Models\User;
use PHPUnit\Framework\TestCase;
use Tests\Helpers\TestDatabaseHelper;

class DefaultAuthTest extends TestCase
{
    protected function setUp(): void
    {
        TestDatabaseHelper::createTestDatabase();
    }

    protected function tearDown(): void
    {
        TestDatabaseHelper::dropTestDatabase();
    }

    public function testDatabaseConnection(): void
    {
        $em = DoctrineRegistry::get();
        $user = new User('email', 'username', 'password');
        $em->persist($user);
        $em->flush();
        $dbUser = $em->getRepository(User::class)->find($user->getId());

        $this::assertInstanceOf(User::class, $dbUser);
    }

}
