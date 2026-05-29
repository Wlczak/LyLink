<?php

namespace Lylink;

use Doctrine\ORM\EntityManagerInterface;

class DoctrineRegistry
{
    private static EntityManagerInterface $entityManager;

    public static function get(): EntityManagerInterface
    {
        return self::$entityManager;
    }

    /**
     * @param EntityManagerInterface $entityManager
     */
    public static function set(EntityManagerInterface $entityManager): void
    {
        self::$entityManager = $entityManager;
    }
}
