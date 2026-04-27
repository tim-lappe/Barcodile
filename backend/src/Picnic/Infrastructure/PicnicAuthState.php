<?php

declare(strict_types=1);

namespace App\Picnic\Infrastructure;

final class PicnicAuthState
{
    private ?string $authKey = null;

    public function getAuthKey(): ?string
    {
        return $this->authKey;
    }

    public function setAuthKey(?string $authKey): void
    {
        $this->authKey = $authKey;
    }
}
