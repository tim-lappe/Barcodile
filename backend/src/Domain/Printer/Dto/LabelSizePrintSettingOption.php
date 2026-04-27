<?php

declare(strict_types=1);

namespace App\Domain\Printer\Dto;

final readonly class LabelSizePrintSettingOption
{
    public function __construct(
        public string $value,
        public string $label,
    ) {
    }

    /**
     * @return array{value: string, label: string}
     */
    public function toArray(): array
    {
        return [
            'value' => $this->value,
            'label' => $this->label,
        ];
    }
}
