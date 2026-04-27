<?php

declare(strict_types=1);

namespace App\Picnic\Application\Dto;

use Symfony\Component\Serializer\Attribute\SerializedName;

final readonly class PicnicIntegrationSettingsResponse
{
    public function __construct(
        #[SerializedName('id')]
        public string $resourceId,
        public ?string $username,
        public string $countryCode,
        public bool $hasStoredPassword,
        public bool $hasStoredAuthSession,
    ) {
    }
}
