<?php

namespace Lylink\Routes\Integrations\Api {
    final class IntegrationApiState
    {
        public static string $input = '';
        /** @var list<string> */
        public static array $headers = [];
        public static int $responseCode = 200;
    }

    function file_get_contents(string $filename): string
    {
        return IntegrationApiState::$input;
    }

    function header(string $header, bool $replace = true, int $response_code = 0): void
    {
        IntegrationApiState::$headers[] = $header;
    }

    function http_response_code(?int $response_code = null): int
    {
        if ($response_code !== null) {
            IntegrationApiState::$responseCode = $response_code;
        }

        return IntegrationApiState::$responseCode;
    }
}

namespace Tests\Routes\Integrations\Api {

    use Lylink\Auth\AuthSession;
    use Lylink\DoctrineRegistry;
    use Lylink\Models\Settings;
    use Lylink\Models\User;
    use Lylink\Routes\Integrations\Api\IntegrationApi;
    use Lylink\Routes\Integrations\Api\IntegrationApiState;
    use PHPUnit\Framework\TestCase;
    use Tests\Helpers\TestDatabaseHelper;

    class IntegrationApiTest extends TestCase
    {
        protected function setUp(): void
        {
            if (session_status() !== PHP_SESSION_ACTIVE) {
                session_start();
            }

            $_SESSION = [];
            $_POST = [];
            $_GET = [];

            IntegrationApiState::$input = '';
            IntegrationApiState::$headers = [];
            IntegrationApiState::$responseCode = 200;

            TestDatabaseHelper::createTestDatabase();
        }

        protected function tearDown(): void
        {
            $_SESSION = [];
            $_POST = [];
            $_GET = [];

            IntegrationApiState::$input = '';
            IntegrationApiState::$headers = [];
            IntegrationApiState::$responseCode = 200;

            session_destroy();
            TestDatabaseHelper::dropTestDatabase();
        }

        public function testAddJellyfinRejectsEmptyInput(): void
        {
            IntegrationApiState::$input = '';

            $this::assertSame('', IntegrationApi::addJellyfin());
            $this::assertSame(400, IntegrationApiState::$responseCode);
        }

        public function testAddJellyfinRejectsMissingAuth(): void
        {
            $encoded = json_encode([
                'address' => 'http://jellyfin.test',
                'token' => 'token',
            ]);
            $this::assertIsString($encoded);
            IntegrationApiState::$input = $encoded;

            $this::assertSame('', IntegrationApi::addJellyfin());
            $this::assertSame(401, IntegrationApiState::$responseCode);
        }

        public function testAddJellyfinRejectsNullUser(): void
        {
            $encoded = json_encode([
                'address' => 'http://jellyfin.test',
                'token' => 'token',
            ]);
            $this::assertIsString($encoded);
            IntegrationApiState::$input = $encoded;

            $auth = new class implements \Lylink\Interfaces\Auth\Authorizator {
                public function login(string $usernamemail, #[\SensitiveParameter] string $password): array
                {
                    return ['errors' => [], 'success' => false, 'usermail' => $usernamemail];
                }

                public function logout(): void
                {
                }

                public function isAuthorized(): bool
                {
                    return true;
                }

                public function getUser(): ?User
                {
                    return null;
                }
            };

            AuthSession::set($auth);

            $this::assertSame('', IntegrationApi::addJellyfin());
            $this::assertSame(401, IntegrationApiState::$responseCode);
        }

        public function testAddJellyfinUpdatesSettings(): void
        {
            $user = new User('api@test.test', 'api', password_hash('Aa123456', PASSWORD_BCRYPT));
            DoctrineRegistry::get()->persist($user);
            DoctrineRegistry::get()->flush();

            $auth = new class($user) implements \Lylink\Interfaces\Auth\Authorizator {
                public function __construct(private User $user)
                {
                }

                public function login(string $usernamemail, #[\SensitiveParameter] string $password): array
                {
                    return ['errors' => [], 'success' => true, 'usermail' => $usernamemail];
                }

                public function logout(): void
                {
                }

                public function isAuthorized(): bool
                {
                    return true;
                }

                public function getUser(): User
                {
                    return $this->user;
                }
            };
            AuthSession::set($auth);

            $encoded = json_encode([
                'address' => 'http://jellyfin.test',
                'token' => 'token',
            ]);
            $this::assertIsString($encoded);
            IntegrationApiState::$input = $encoded;

            $this::assertSame('{"success":true}', IntegrationApi::addJellyfin());
            $this::assertSame(200, IntegrationApiState::$responseCode);

            $settings = DoctrineRegistry::get()->getRepository(Settings::class)->findOneBy(['user_id' => $user->getId()]);
            $this::assertInstanceOf(Settings::class, $settings);
            $this::assertSame('http://jellyfin.test', $settings->jellyfin_server);
            $this::assertSame('token', $settings->jellyfin_token);
            $this::assertTrue($settings->jellyfin_connected);
            $this::assertTrue($settings->allow_jellyfin_login);
        }
    }
}
