<?php

declare(strict_types=1);

namespace App\Picnic\Domain\Facade;

final readonly class PicnicSettingsView
{
    public function __construct(
        public string $resourceId,
        public ?string $username,
        public string $countryCode,
        public bool $hasStoredPassword,
        public bool $hasStoredAuthSession,
    ) {
    }
}
