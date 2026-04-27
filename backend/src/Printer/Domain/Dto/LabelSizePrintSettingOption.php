<?php

declare(strict_types=1);

namespace App\Printer\Domain\Dto;

final readonly class LabelSizePrintSettingOption
{
    public function __construct(
        public string $value,
        public string $label,
    ) {
    }
}
