<?php

declare(strict_types=1);

namespace App\Printer\Application\Dto;

use Symfony\Component\Serializer\Attribute\SerializedName;

final readonly class PrinterDeviceResponse
{
    /**
     * @param array<string, mixed> $connection
     * @param array<string, mixed> $printSettings
     */
    public function __construct(
        #[SerializedName('id')]
        public string $resourceId,
        public string $driverCode,
        public array $connection,
        public array $printSettings,
        public string $name,
    ) {
    }
}
