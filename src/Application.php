<?php

namespace src;

use Illuminate\Container\Container;
use src\Models\OcConfig\OcConfig;
use src\Models\User\User;

final class Application extends Container
{
    /** @var ?User $user */
    protected $user = null;

    public function __construct()
    {
        $this->singleton(OcConfig::class);
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setAuthorizedUser(User $user = null): void
    {
        $this->user = $user;
    }

    public function getOcConfig(): OcConfig
    {
        return $this->make(OcConfig::class);
    }
}
