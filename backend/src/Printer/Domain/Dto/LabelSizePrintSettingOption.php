<?php

declare(strict_types=1);

namespace App\Printer\Domain\Dto;

use App\SharedKernel\Domain\Label\LabelSize;

final readonly class LabelSizePrintSettingOption
{
    public function __construct(
        public string $value,
        public string $label,
        public LabelSize $size,
    ) {
    }
}
