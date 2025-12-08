<?php

use Lylink\Data\EnvStore;
use PharIo\Manifest\InvalidEmailException;
use PHPUnit\Framework\Attributes\DataProvider;
use Uri\InvalidUriException;

class EnvStoreTest extends PHPUnit\Framework\TestCase
{
    public string $default_base_domain = 'base.domain.test';

    public function testEnvStoreType(): void
    {
        $env = new EnvStore(stmp_host: 'stmp.test.test', stmp_username: 'test@test.test', stmp_password: 'test', client_id: 'test', client_secret: 'test', base_domain: $this->default_base_domain);
        $this->assertInstanceOf(EnvStore::class, $env);
    }

    public function testConstructor(): void
    {
        $env = new EnvStore(stmp_host: 'stmp.test.test', stmp_username: 'test@test.test', stmp_password: 'test', client_id: 'test', client_secret: 'test', base_domain: $this->default_base_domain);
        $this->assertSame('stmp.test.test', $env->SMTP_HOST);
        $this->assertSame('test@test.test', $env->SMTP_USERNAME);
        $this->assertSame('test', $env->SMTP_PASSWORD);
        $this->assertSame('test', $env->CLIENT_ID);
        $this->assertSame('test', $env->CLIENT_SECRET);
        $this->assertSame($this->default_base_domain, $env->BASE_DOMAIN);
    }

    /**
     * @return list<array{string,bool}>
     */
    public static function hostProvider(): array
    {
        return [['stmp.test.test', false], ['test', false], ['', true], ['test@', true], ['@test', false], ['test@stmp.test.test', false]];
    }

    #[DataProvider('hostProvider')]
    public function testStmpHost(string $stmp_host, bool $expectedToFail): void
    {
        if ($expectedToFail) {
            $this->expectException(InvalidUriException::class);
        }
        $env = new EnvStore(stmp_host: $stmp_host, stmp_username: 'test@test.test', stmp_password: 'test', client_id: 'test', client_secret: 'test', base_domain: $this->default_base_domain);
        $this->assertSame($stmp_host, $env->SMTP_HOST);
    }

    /**
     * @return list<array{string,bool}>
     */
    public static function emailProvider(): array
    {
        return [['test@test.test', false], ['test', true], ['', true], ['test@', true], ['@test', true], ['test@stmp.test.test', false], ['https://test@stmp.test.test', true], ['stmp://test@stmp.test.test', true]];
    }

    #[DataProvider('emailProvider')]
    public function testEmail(string $email, bool $expectedToFail): void
    {
        if ($expectedToFail) {
            $this->expectException(InvalidEmailException::class);
        }
        $env = new EnvStore(stmp_host: 'stmp.test.test', stmp_username: $email, stmp_password: 'test', client_id: 'test', client_secret: 'test', base_domain: $this->default_base_domain);
        $this->assertSame($email, $env->SMTP_USERNAME);
    }

    /**
     * @return list<array{string,bool}>
     */
    public static function passwordProvider(): array
    {
        return [['test', false], ['', true], ['test@', false], ['@test', false], ['test@stmp.test.test', false]];
    }

    #[DataProvider('passwordProvider')]
    public function testPassword(string $password, bool $expectedToFail): void
    {
        if ($expectedToFail) {
            $this->expectException(Exception::class);
            $this->expectExceptionMessage('Invalid SMTP_PASSWORD environment variable');
        }
        $env = new EnvStore(stmp_host: 'stmp.test.test', stmp_username: 'test@test.test', stmp_password: $password, client_id: 'test', client_secret: 'test', base_domain: $this->default_base_domain);
        $this->assertSame($password, $env->SMTP_PASSWORD);
    }

}
