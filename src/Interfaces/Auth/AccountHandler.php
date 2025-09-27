<?php

namespace Lylink\Interfaces\Auth;

use SensitiveParameter;

interface AccountHandler
{
    /**
     * @return array{errors: list<string>, success: bool, old: array{email: string, username: string}}
     */
    public function register(string $email, string $username, #[SensitiveParameter] string $pass, #[SensitiveParameter] string $passCheck): array;
}
