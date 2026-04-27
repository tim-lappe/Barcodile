<?php

declare(strict_types=1);

namespace App\Domain\Printer\Dto;

final readonly class ColorModePrintSettingOption
{
    public function __construct(
        public string $value,
        public string $label,
        public bool $red,
    ) {
    }

    /**
     * @return array{value: string, label: string, red: bool}
     */
    public function toArray(): array
    {
        return [
            'value' => $this->value,
            'label' => $this->label,
            'red' => $this->red,
        ];
    }
}
