<?php

namespace Lylink;

use Doctrine\ORM\EntityManager;

class DoctrineRegistry
{
    private static EntityManager $entityManager;

    public static function get(): EntityManager
    {
        return self::$entityManager;
    }

    /**
     * @param EntityManager $entityManager
     */
    public static function set(EntityManager $entityManager): void
    {
        self::$entityManager = $entityManager;
    }
}
