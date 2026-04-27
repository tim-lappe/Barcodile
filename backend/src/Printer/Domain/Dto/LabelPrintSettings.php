<?php

declare(strict_types=1);

namespace App\Printer\Domain\Dto;

interface LabelPrintSettings
{
    /**
     * @return array<string, mixed>
     */
    public function printSettingsData(): array;
}
