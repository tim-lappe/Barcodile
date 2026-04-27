<?php

declare(strict_types=1);

namespace App\Picnic\Domain\Port;

use App\Picnic\Domain\ValueObject\PicnicPendingLoginCredentials;

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
