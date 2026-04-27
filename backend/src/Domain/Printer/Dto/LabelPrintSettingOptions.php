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

    /**
     * @return array{labelSizes: list<array{value: string, label: string}>, colorModes: list<array{value: string, label: string, red: bool}>}
     */
    public function toArray(): array
    {
        return [
            'labelSizes' => array_map(
                static fn (LabelSizePrintSettingOption $option): array => $option->toArray(),
                $this->labelSizes,
            ),
            'colorModes' => array_map(
                static fn (ColorModePrintSettingOption $option): array => $option->toArray(),
                $this->colorModes,
            ),
        ];
    }
}
