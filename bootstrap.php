<?php

use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Lylink\DoctrineRegistry;

require_once __DIR__ . '/vendor/autoload.php';
                                                          // Create a simple "default" Doctrine ORM configuration for Attributes
$config = ORMSetup::createAttributeMetadataConfiguration( // on PHP < 8.4, use ORMSetup::createAttributeMetadataConfiguration()
    paths: [__DIR__ . '/src/Models'],
    isDevMode: true,
);

// configuring the database connection
$connection = DriverManager::getConnection([
    'driver' => 'pdo_sqlite',
    'path' => __DIR__ . '/db/lyrics.db'
], $config);
// obtaining the entity manager
$entityManager = new EntityManager($connection, $config);

DoctrineRegistry::set($entityManager);
