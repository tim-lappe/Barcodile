<?php

declare(strict_types=1);

namespace App\Printer\Api\Dto;

final readonly class PrinterDriverListItemResponse
{
    /**
     * @param array<string, mixed> $defaultPrintSettings
     * @param array<string, mixed> $printSettingOptions
     */
    public function __construct(
        public string $code,
        public string $label,
        public array $defaultPrintSettings,
        public array $printSettingOptions,
    ) {
    }
}
