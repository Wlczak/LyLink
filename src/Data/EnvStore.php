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

    public function __construct(string $stmp_host = "", string $stmp_username = "", string $stmp_password = "", string $client_id = "", string $client_secret = "", string $base_domain = "")
    {
        if (filter_var("stmp://" . $stmp_host, FILTER_VALIDATE_URL)) {
            $this->SMTP_HOST = $stmp_host;
        } else {
            $this->SMTP_HOST = "";
            throw new InvalidUriException("Invalid SMTP_HOST environment variable");
        }
        if (filter_var($stmp_username, FILTER_VALIDATE_EMAIL)) {
            $this->SMTP_USERNAME = $stmp_username;
        } else {
            $this->SMTP_USERNAME = "";
            throw new InvalidEmailException("Invalid SMTP_USERNAME environment variable");
        }
        if ($stmp_password != "") {
            $this->SMTP_PASSWORD = $stmp_password;
        } else {
            $this->SMTP_PASSWORD = "";
            throw new Exception("Invalid SMTP_PASSWORD environment variable");
        }
        if ($client_id != "") {
            $this->CLIENT_ID = $client_id;
        } else {
            $this->CLIENT_ID = "";
            throw new Exception("Invalid CLIENT_ID environment variable");
        }
        if ($client_secret != "") {
            $this->CLIENT_SECRET = $client_secret;
        } else {
            $this->CLIENT_SECRET = "";
            throw new Exception("Invalid CLIENT_SECRET environment variable");
        }
        if (filter_var($base_domain, FILTER_VALIDATE_URL)) {
            $this->BASE_DOMAIN = $base_domain;
        } else {
            $this->BASE_DOMAIN = "";
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
