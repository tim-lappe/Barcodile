<?php

declare(strict_types=1);

namespace App\Application\Scanner\Dto;

use Symfony\Component\Serializer\Attribute\SerializedName;

final readonly class ScannerDeviceResponse
{
    /**
     * @param list<string> $lastScannedCodes
     */
    public function __construct(
        #[SerializedName('id')]
        public string $resourceId,
        public string $deviceIdentifier,
        public string $name,
        public array $lastScannedCodes,
    ) {
    }
}
