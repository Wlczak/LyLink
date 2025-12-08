<?php

namespace Tests\Auth;

use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use Lylink\DoctrineRegistry;
use Lylink\Models\User;
use PHPUnit\Framework\TestCase;

class DefaultAuthTest extends TestCase
{
    protected function setUp(): void
    {
        $config = ORMSetup::createAttributeMetadataConfiguration( // on PHP < 8.4, use ORMSetup::createAttributeMetadataConfiguration()
            paths: [__DIR__ . '/../../src/Models'],
            isDevMode: true,
        );

        // configuring the database connection
        $connection = DriverManager::getConnection([
            'driver' => 'pdo_sqlite',
            'path' => __DIR__ . '/lyrics.db'
        ], $config);
        // obtaining the entity manager
        $entityManager = new EntityManager($connection, $config);

        DoctrineRegistry::set($entityManager);
        $schemaTool = new SchemaTool($entityManager);
        $metadata = $entityManager->getMetadataFactory()->getAllMetadata();

        $schemaTool->dropDatabase();
        $schemaTool->createSchema($metadata);
    }

    protected function tearDown(): void
    {
        unlink(__DIR__ . '/lyrics.db');
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
