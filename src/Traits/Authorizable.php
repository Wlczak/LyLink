<?php

namespace Lylink\Traits;

trait Authorizable
{
    private bool $authorized = false;
    public function isAuthorized(): bool
    {
        return $this->authorized;
    }

    public function logout(): void
    {
        $this->authorized = false;
    }

    private int $uid;
    public function getUid(): int
    {
        return $this->uid;
    }
}
