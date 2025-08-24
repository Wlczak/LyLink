<?php

use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;

require_once __DIR__ . '/vendor/autoload.php';
                                                          // Create a simple "default" Doctrine ORM configuration for Attributes
$config = ORMSetup::createAttributeMetadataConfiguration( // on PHP < 8.4, use ORMSetup::createAttributeMetadataConfiguration()
    paths: [__DIR__ . '/src'],
    isDevMode: true,
);

// configuring the database connection
$connection = DriverManager::getConnection([
    'driver' => 'pdo_sqlite',
    'path' => __DIR__ . '/lyrics.sqlite'
], $config);
// obtaining the entity manager
$entityManager = new EntityManager($connection, $config);
