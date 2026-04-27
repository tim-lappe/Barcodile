<?php

declare(strict_types=1);

namespace App\Printer\Api\Dto;

use Symfony\Component\Serializer\Attribute\SerializedName;

final readonly class DiscoveredPrinterOptionResponse
{
    /**
     * @param array<string, mixed> $suggestedConnection
     * @param array<string, mixed> $suggestedSettings
     */
    public function __construct(
        public string $deviceIdentifier,
        public string $label,
        public array $suggestedConnection,
        #[SerializedName('suggestedPrintSettings')]
        public array $suggestedSettings,
    ) {
    }
}
