<?php

declare(strict_types=1);

namespace App\Domain\Printer\Dto;

final readonly class LabelPrintSettingOptions
{
    /**
     * @param list<LabelSizePrintSettingOption> $labelSizes
     * @param list<ColorModePrintSettingOption> $colorModes
     */
    public function __construct(
        public array $labelSizes,
        public array $colorModes,
    ) {
    }
}
