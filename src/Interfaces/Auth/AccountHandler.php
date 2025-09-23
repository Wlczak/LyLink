<?php

namespace Lylink\Interfaces\Auth;

interface AccountHandler
{
    /**
     * @return array{errors: list<string>, success: bool, old: array{email: string, username: string}}
     */
    public function register(string $email, string $username, string $pass, string $passCheck): array;
}
