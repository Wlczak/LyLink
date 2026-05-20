<?php

namespace Tests\Helpers;

use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Tools\SchemaTool;
use Lylink\DoctrineRegistry;
use Lylink\Models\Settings;
use Lylink\Models\User;
use SQLite3;

class TestDatabaseHelper
{
    private static bool $fakeDatabase = false;
    /** @var array<int,User> */
    private static array $users = [];
    /** @var array<int,Settings> */
    private static array $settings = [];
    private static int $nextUserId = 1;
    private static int $nextSettingsId = 1;

    public static function getDatabasePath(): string
    {
        return __DIR__ . '/lyrics.db';
    }

    public static function createTestDatabase(): void
    {
        self::$fakeDatabase = !extension_loaded('pdo_sqlite') || !class_exists(SQLite3::class);
        self::$users = [];
        self::$settings = [];
        self::$nextUserId = 1;
        self::$nextSettingsId = 1;

        if (self::$fakeDatabase) {
            DoctrineRegistry::set(self::createFakeEntityManager());
            return;
        }

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
        if (self::$fakeDatabase) {
            self::$users = [];
            self::$settings = [];
            return;
        }

        if (file_exists(self::getDatabasePath())) {
            unlink(self::getDatabasePath());
        }
    }

    /**
     * @return array<string,mixed>|false
     */
    public static function queryDatabase(string $query): array | false
    {
        if (self::$fakeDatabase) {
            return self::queryFakeDatabase($query);
        }

        $db = new SQLite3(self::getDatabasePath());
        $results = $db->query($query);

        if ($results !== false) {

            /**
             * @var array<string,mixed>|false
             */
            $assoc = $results->fetchArray(SQLITE3_ASSOC);
            if ($assoc === false) {
                return [];
            } else {
                return $assoc;
            }
        } else {
            return false;
        }
    }

    private static function createFakeEntityManager(): EntityManager
    {
        return new class extends EntityManager {
            public function __construct()
            {
            }

            public function getRepository(string $className): EntityRepository
            {
                return TestDatabaseHelper::createFakeRepository($className);
            }

            public function persist(object $object): void
            {
                TestDatabaseHelper::fakePersist($object);
            }

            public function flush(): void
            {
            }

            public function remove(object $object): void
            {
                TestDatabaseHelper::fakeRemove($object);
            }
        };
    }

    private static function createFakeRepository(string $className): EntityRepository
    {
        return new class($className) extends EntityRepository {
            public function __construct(private string $className)
            {
            }

            public function find(mixed $id, \Doctrine\DBAL\LockMode|int|null $lockMode = null, int|null $lockVersion = null): object|null
            {
                return TestDatabaseHelper::fakeFind($this->className, $id);
            }

            public function findAll(): array
            {
                return TestDatabaseHelper::fakeFindAll($this->className);
            }

            public function findBy(array $criteria, array|null $orderBy = null, int|null $limit = null, int|null $offset = null): array
            {
                return TestDatabaseHelper::fakeFindBy($this->className, $criteria);
            }

            public function findOneBy(array $criteria, array|null $orderBy = null): object|null
            {
                return TestDatabaseHelper::fakeFindOneBy($this->className, $criteria);
            }

            public function count(array $criteria = []): int
            {
                return count(TestDatabaseHelper::fakeFindBy($this->className, $criteria));
            }
        };
    }

    private static function fakePersist(object $object): void
    {
        if ($object instanceof User) {
            if ($object->getId() === null) {
                self::setPrivateProperty($object, 'id', self::$nextUserId++);
            }
            self::$users[$object->getId() ?? 0] = $object;
            return;
        }

        if ($object instanceof Settings) {
            if ($object->getId() === null) {
                self::setPrivateProperty($object, 'id', self::$nextSettingsId++);
            }
            self::$settings[$object->getUserId()] = $object;
        }
    }

    private static function fakeRemove(object $object): void
    {
        if ($object instanceof User) {
            $id = $object->getId();
            if ($id !== null) {
                unset(self::$users[$id]);
            }
            return;
        }

        if ($object instanceof Settings) {
            unset(self::$settings[$object->getUserId()]);
        }
    }

    private static function fakeFind(string $className, mixed $id): object|null
    {
        if ($className === User::class && is_int($id) && isset(self::$users[$id])) {
            return self::$users[$id];
        }

        if ($className === Settings::class && is_int($id)) {
            foreach (self::$settings as $settings) {
                if ($settings->getId() === $id) {
                    return $settings;
                }
            }
        }

        return null;
    }

    /**
     * @return list<object>
     */
    private static function fakeFindAll(string $className): array
    {
        if ($className === User::class) {
            return array_values(self::$users);
        }

        if ($className === Settings::class) {
            return array_values(self::$settings);
        }

        return [];
    }

    /**
     * @param array<string,mixed> $criteria
     * @return list<object>
     */
    private static function fakeFindBy(string $className, array $criteria): array
    {
        $results = [];

        if ($className === User::class) {
            foreach (self::$users as $user) {
                $matches = true;
                foreach ($criteria as $field => $value) {
                    if ($field === 'email' && $user->getEmail() !== $value) {
                        $matches = false;
                    }
                    if ($field === 'username' && $user->username !== $value) {
                        $matches = false;
                    }
                    if ($field === 'id' && $user->getId() !== $value) {
                        $matches = false;
                    }
                }
                if ($matches) {
                    $results[] = $user;
                }
            }
        }

        if ($className === Settings::class) {
            foreach (self::$settings as $settings) {
                $matches = true;
                foreach ($criteria as $field => $value) {
                    if ($field === 'user_id' && $settings->getUserId() !== $value) {
                        $matches = false;
                    }
                    if ($field === 'id' && $settings->getId() !== $value) {
                        $matches = false;
                    }
                }
                if ($matches) {
                    $results[] = $settings;
                }
            }
        }

        return $results;
    }

    private static function fakeFindOneBy(string $className, array $criteria): object|null
    {
        $results = self::fakeFindBy($className, $criteria);
        return $results[0] ?? null;
    }

    /**
     * @return array<string,mixed>|false
     */
    private static function queryFakeDatabase(string $query): array|false
    {
        if (preg_match('/^INSERT INTO users \(email, password, username, emailVerified\) VALUES \("([^"]*)", "([^"]*)", "([^"]*)", ([01])\)$/', trim($query), $matches) !== 1) {
            return false;
        }

        $user = new User($matches[1], $matches[3], $matches[2]);
        self::fakePersist($user);
        if ((int) $matches[4] === 1) {
            $user->verifyEmail();
        }

        return [
            'email' => $matches[1],
            'password' => $matches[2],
            'username' => $matches[3],
            'emailVerified' => (int) $matches[4],
        ];
    }

    private static function setPrivateProperty(object $object, string $property, mixed $value): void
    {
        $ref = new \ReflectionObject($object);
        while (!$ref->hasProperty($property) && $ref = $ref->getParentClass()) {
        }

        if (!$ref->hasProperty($property)) {
            return;
        }

        $prop = $ref->getProperty($property);
        $prop->setAccessible(true);
        $prop->setValue($object, $value);
    }
}
