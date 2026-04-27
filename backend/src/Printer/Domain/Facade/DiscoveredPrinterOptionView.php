<?php

declare(strict_types=1);

namespace App\Printer\Domain\Facade;

final readonly class DiscoveredPrinterOptionView
{
    /**
     * @param array<string, mixed> $suggestedConnection
     * @param array<string, mixed> $suggestedSettings
     */
    public function __construct(
        public string $deviceIdentifier,
        public string $label,
        public array $suggestedConnection,
        public array $suggestedSettings,
    ) {
    }
}
