<?php

declare(strict_types=1);

namespace App\Application\Printer\Dto;

final readonly class DiscoveredPrinterOptionResponse
{
    /**
     * @param array<string, mixed> $suggestedConnection
     */
    public function __construct(
        public string $deviceIdentifier,
        public string $label,
        public array $suggestedConnection,
    ) {
    }
}
