<?php

namespace Lylink\Data;

use Exception;
use PharIo\Manifest\InvalidEmailException;
use Uri\InvalidUriException;
use Uri\Rfc3986\Uri;

class EnvStore
{
    public readonly string $SMTP_HOST;
    public readonly string $SMTP_USERNAME;
    public readonly string $SMTP_PASSWORD;

    public function __construct(string $stmp_host = "", string $stmp_username = "", string $stmp_password = "")
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

    }
}
