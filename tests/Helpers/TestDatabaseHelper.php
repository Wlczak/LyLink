<?php

namespace Tests\Helpers;

use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use Lylink\DoctrineRegistry;

class TestDatabaseHelper
{
    public static function getDatabasePath(): string
    {
        return __DIR__ . '/lyrics.db';
    }

    public static function createTestDatabase(): void
    {
        $config = ORMSetup::createAttributeMetadataConfiguration( // on PHP < 8.4, use ORMSetup::createAttributeMetadataConfiguration()
            paths: [__DIR__ . '/../../src/Models'],
            isDevMode: true,
        );

        // configuring the database connection
        $connection = DriverManager::getConnection([
            'driver' => 'pdo_sqlite',
            'path' => self::getDatabasePath()
        ], $config);
        // obtaining the entity manager
        $entityManager = new EntityManager($connection, $config);

        DoctrineRegistry::set($entityManager);
        $schemaTool = new SchemaTool($entityManager);
        $metadata = $entityManager->getMetadataFactory()->getAllMetadata();

        $schemaTool->dropDatabase();
        $schemaTool->createSchema($metadata);
    }

    public static function dropTestDatabase(): void
    {
        unlink(self::getDatabasePath());
    }
}
