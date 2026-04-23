<?php

declare(strict_types=1);

namespace App\Domain\Picnic\Port;

use App\Domain\Picnic\ValueObject\PicnicPasswordLoginResult;
use App\Domain\Picnic\ValueObject\PicnicPendingLoginCredentials;

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
