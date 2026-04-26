<?php

declare(strict_types=1);

namespace App\Application\Printer\Dto;

use Symfony\Component\Serializer\Attribute\SerializedName;

final readonly class PrinterDeviceResponse
{
    /**
     * @param array<string, mixed> $connection
     */
    public function __construct(
        #[SerializedName('id')]
        public string $resourceId,
        public string $driverCode,
        public array $connection,
        public string $name,
    ) {
    }
}
