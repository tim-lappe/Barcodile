<?php

declare(strict_types=1);

namespace App\Picnic\Infrastructure\Domains\Consent;

enum ConsentSettingsPageKind
{
    case Standard;
    case General;
}
