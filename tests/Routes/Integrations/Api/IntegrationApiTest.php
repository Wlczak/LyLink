<?php

namespace Lylink\Routes\Integrations\Api {
    function file_get_contents(string $filename): string|false
    {
        return $GLOBALS['integration_api_input'] ?? '';
    }

    function header(string $header, bool $replace = true, int $response_code = 0): void
    {
        $GLOBALS['integration_api_headers'][] = $header;
    }

    function http_response_code(?int $response_code = null): int|false
    {
        if ($response_code !== null) {
            $GLOBALS['integration_api_response_code'] = $response_code;
        }

        return $GLOBALS['integration_api_response_code'] ?? 200;
    }
}

namespace Tests\Routes\Integrations\Api {

    use Lylink\Auth\AuthSession;
    use Lylink\DoctrineRegistry;
    use Lylink\Models\User;
    use Lylink\Routes\Integrations\Api\IntegrationApi;
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

            $GLOBALS['integration_api_input'] = '';
            $GLOBALS['integration_api_headers'] = [];
            $GLOBALS['integration_api_response_code'] = 200;

            TestDatabaseHelper::createTestDatabase();
        }

        protected function tearDown(): void
        {
            $_SESSION = [];
            $_POST = [];
            $_GET = [];
            unset($GLOBALS['integration_api_input'], $GLOBALS['integration_api_headers'], $GLOBALS['integration_api_response_code']);

            session_destroy();
            TestDatabaseHelper::dropTestDatabase();
        }

        public function testAddJellyfinRejectsEmptyInput(): void
        {
            $GLOBALS['integration_api_input'] = '';

            $this::assertSame('', IntegrationApi::addJellyfin());
            $this::assertSame(400, $GLOBALS['integration_api_response_code']);
        }

        public function testAddJellyfinRejectsMissingAuth(): void
        {
            $GLOBALS['integration_api_input'] = json_encode([
                'address' => 'http://jellyfin.test',
                'token' => 'token',
            ]);

            $this::assertSame('', IntegrationApi::addJellyfin());
            $this::assertSame(401, $GLOBALS['integration_api_response_code']);
        }

        public function testAddJellyfinRejectsNullUser(): void
        {
            $GLOBALS['integration_api_input'] = json_encode([
                'address' => 'http://jellyfin.test',
                'token' => 'token',
            ]);

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
            $this::assertSame(401, $GLOBALS['integration_api_response_code']);
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

                public function getUser(): ?User
                {
                    return $this->user;
                }
            };
            AuthSession::set($auth);

            $GLOBALS['integration_api_input'] = json_encode([
                'address' => 'http://jellyfin.test',
                'token' => 'token',
            ]);

            $this::assertSame('{"success":true}', IntegrationApi::addJellyfin());
            $this::assertSame(200, $GLOBALS['integration_api_response_code']);

            $settings = DoctrineRegistry::get()->getRepository(\Lylink\Models\Settings::class)->findOneBy(['user_id' => $user->getId()]);
            $this::assertNotNull($settings);
            $this::assertSame('http://jellyfin.test', $settings?->jellyfin_server);
            $this::assertSame('token', $settings?->jellyfin_token);
            $this::assertTrue($settings?->jellyfin_connected ?? false);
            $this::assertTrue($settings?->allow_jellyfin_login ?? false);
        }
    }
}
