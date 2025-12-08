<?php

namespace Lylink\Data;

use Exception;
use PharIo\Manifest\InvalidEmailException;
use Uri\InvalidUriException;

class EnvStore
{
    public readonly string $SMTP_HOST;
    public readonly string $SMTP_USERNAME;
    public readonly string $SMTP_PASSWORD;
    public readonly string $CLIENT_ID;
    public readonly string $CLIENT_SECRET;
    public readonly string $BASE_DOMAIN;

    public function __construct(string $stmp_host, string $stmp_username, string $stmp_password, string $client_id, string $client_secret, string $base_domain)
    {
        $this->SMTP_HOST = $stmp_host;
        $this->SMTP_USERNAME = $stmp_username;
        $this->SMTP_PASSWORD = $stmp_password;
        $this->CLIENT_ID = $client_id;
        $this->CLIENT_SECRET = $client_secret;
        $this->BASE_DOMAIN = $base_domain;
        $this->validate();
    }

    private function validate(): void
    {
        if ($this->SMTP_HOST === "" || !filter_var("stmp://" . $this->SMTP_HOST, FILTER_VALIDATE_URL)) {
            throw new InvalidUriException("Invalid SMTP_HOST environment variable");
        }
        if ($this->SMTP_USERNAME === "" || !filter_var($this->SMTP_USERNAME, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidEmailException("Invalid SMTP_USERNAME environment variable");
        }
        if ($this->SMTP_PASSWORD === "") {
            throw new Exception("Invalid SMTP_PASSWORD environment variable");
        }
        if ($this->CLIENT_ID === "") {
            throw new Exception("Invalid CLIENT_ID environment variable");
        }
        if ($this->CLIENT_SECRET === "") {
            throw new Exception("Invalid CLIENT_SECRET environment variable");
        }
        if ($this->BASE_DOMAIN === "" || !filter_var("http://" . $this->BASE_DOMAIN, FILTER_VALIDATE_URL)) {
            throw new InvalidUriException("Invalid BASE_DOMAIN environment variable");
        }
    }

    public static function load(): self
    {
        /**
         * @var string|int|float|bool|null
         */
        $stmp_host = $_ENV['SMTP_HOST'];
        /**
         * @var string|int|float|bool|null
         */
        $stmp_username = $_ENV['SMTP_USERNAME'];
        /**
         * @var string|int|float|bool|null
         */
        $stmp_password = $_ENV['SMTP_PASSWORD'];
        /**
         * @var string|int|float|bool|null
         */
        $client_id = $_ENV['CLIENT_ID'];
        /**
         * @var string|int|float|bool|null
         */
        $client_secret = $_ENV['CLIENT_SECRET'];
        /**
         * @var string|int|float|bool|null
         */
        $base_domain = $_ENV['BASE_DOMAIN'];

        return new self(
            stmp_host: strval($stmp_host),
            stmp_username: strval($stmp_username),
            stmp_password: strval($stmp_password),
            client_id: strval($client_id),
            client_secret: strval($client_secret),
            base_domain: strval($base_domain)
        );
    }
}
