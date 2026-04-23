<?php

declare(strict_types=1);

namespace App\Domain\Picnic\ValueObject;

final readonly class PicnicPendingLoginCredentials
{
    public function __construct(
        public string $username,
        public string $countryCode,
        public string $password,
        public string $pendingAuthKey,
    ) {
    }
}
