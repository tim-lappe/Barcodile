<?php

declare(strict_types=1);

namespace App\Picnic\Domain\Port;

use App\Picnic\Domain\ValueObject\PicnicPasswordLoginResult;
use App\Picnic\Domain\ValueObject\PicnicPendingLoginCredentials;

interface PicnicAnonymousAuthenticationPort
{
    public function loginWithPassword(
        string $username,
        string $countryCode,
        string $password,
    ): PicnicPasswordLoginResult;

    public function requestTwoFactorCode(PicnicPendingLoginCredentials $pending, string $channel): void;

    public function verifyTwoFactorCode(PicnicPendingLoginCredentials $pending, string $otp): string;
}
