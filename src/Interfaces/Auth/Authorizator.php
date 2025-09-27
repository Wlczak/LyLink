<?php

namespace Lylink\Interfaces\Auth;

use Lylink\Models\User;
use SensitiveParameter;

interface Authorizator
{
    /**
     * @return array{errors: list<string>, success: bool, usermail: string}
     */
    public function login(string $usernamemail, #[SensitiveParameter] string $password): array;
    public function logout(): void;
    public function isAuthorized(): bool;
    public function getUser(): ?User;
}
