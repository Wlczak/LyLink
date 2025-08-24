#!/usr/bin/env php
<?php
// bin/doctrine
use Doctrine\ORM\Tools\Console\ConsoleRunner;
use Doctrine\ORM\Tools\Console\EntityManagerProvider\SingleManagerProvider;
// Adjust this path to your actual bootstrap.php

require __DIR__ . '/../bootstrap.php';

if (!isset($entityManager)) {
    throw new \Exception('EntityManager is not defined');
}

ConsoleRunner::run(
    new SingleManagerProvider($entityManager)
);

exit(0);