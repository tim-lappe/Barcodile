<?php

declare(strict_types=1);

namespace App\Picnic\Application\Dto;

final readonly class PatchPicnicSettingsRequest
{
    public function __construct(
        public bool $usernameSpecified,
        public mixed $username,
        public bool $countryCodeSpecified,
        public ?string $countryCode,
        public bool $passwordSpecified,
        public ?string $password,
        public bool $authClearSpecified,
        public ?bool $clearAuthSession,
    ) {
    }
}
