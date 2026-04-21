<?php

declare(strict_types=1);

namespace App\Infrastructure\Picnic\Domains\Consent;

enum ConsentSettingsPageKind
{
    case Standard;
    case General;
}
