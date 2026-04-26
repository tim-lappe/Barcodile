<?php

declare(strict_types=1);

namespace App\Domain\Printer\ValueObject;

final readonly class DiscoveredPrinterOption
{
    /**
     * @param array<string, mixed> $suggestedConnection
     */
    public function __construct(
        public string $deviceIdentifier,
        public string $label,
        public array $suggestedConnection = [],
    ) {
    }
}
