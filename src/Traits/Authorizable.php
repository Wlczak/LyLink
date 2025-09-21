<?php

namespace Lylink\Traits;

trait Authorizable
{
    private bool $authorized;
    public function isAuthorized(): bool
    {
        return $this->authorized;
    }

    public function logout(): void
    {
        $this->authorized = false;
    }

    private int $uid;
    public function getUid(): bool
    {
        return $this->uid;
    }
}
