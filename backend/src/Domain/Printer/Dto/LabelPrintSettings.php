<?php

declare(strict_types=1);

namespace App\Domain\Printer\Dto;

interface LabelPrintSettings
{
    /**
     * @return array<string, mixed>
     */
    public function printSettingsData(): array;
}
