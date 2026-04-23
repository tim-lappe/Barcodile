<?php

declare(strict_types=1);

namespace App\Domain\Picnic\Port;

use App\Domain\Picnic\ValueObject\PicnicPendingLoginCredentials;

interface PicnicPendingLoginTokenPort
{
    public function encode(
        string $username,
        string $countryCode,
        string $password,
        string $pendingAuthKey,
    ): string;

    public function decode(string $token): PicnicPendingLoginCredentials;
}
