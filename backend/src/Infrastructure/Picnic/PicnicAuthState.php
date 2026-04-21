<?php

declare(strict_types=1);

namespace App\Infrastructure\Picnic;

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
