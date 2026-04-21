<?php

declare(strict_types=1);

namespace App\Application\Scanner\Dto;

use Symfony\Component\Serializer\Attribute\SerializedName;

final readonly class ScannerDeviceResponse
{
    public function __construct(
        #[SerializedName('id')]
        public string $resourceId,
        public string $deviceIdentifier,
        public string $name,
    ) {
    }
}
