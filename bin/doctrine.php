#!/usr/bin/env php
<?php
// bin/doctrine

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Console\ConsoleRunner;
use Doctrine\ORM\Tools\Console\EntityManagerProvider\SingleManagerProvider;

// Adjust this path to your actual bootstrap.php

require __DIR__ . '/../bootstrap.php';

if (!isset($entityManager)) {
    throw new \Exception('EntityManager is not defined');
}

if (!class_exists(ConsoleRunner::class)) {
    throw new \RuntimeException('You need to add doctrine/orm to your composer.json');
}

if (!$entityManager instanceof EntityManager) {
    throw new \RuntimeException('Invalid EntityManager object provided');
}
ConsoleRunner::run(
    new SingleManagerProvider($entityManager)
);

exit(0);