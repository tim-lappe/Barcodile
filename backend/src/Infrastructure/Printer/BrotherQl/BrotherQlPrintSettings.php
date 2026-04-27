<?php

declare(strict_types=1);

namespace App\Infrastructure\Printer\BrotherQl;

use App\Domain\Printer\Dto\LabelPrintSettings;
use App\Domain\Printer\Exception\LabelPrintJobFailedException;

final readonly class BrotherQlPrintSettings implements LabelPrintSettings
{
    private const DEFAULT_LABEL_SIZE = '62';
    private const DEFAULT_RED = true;
    private const ALLOWED_LABEL_SIZES = [
        '12', '29', '38', '50', '54', '62', '102',
        '17x54', '17x87', '23x23', '29x42', '29x90', '39x48',
        '52x29', '62x29', '62x100', '102x51', '102x152',
        'd12', 'd24', 'd58',
    ];

    private function __construct(
        public string $labelSize,
        public bool $red,
    ) {
        if (!\in_array($labelSize, self::ALLOWED_LABEL_SIZES, true)) {
            throw new LabelPrintJobFailedException('Unsupported Brother QL label size.');
        }
    }

    public static function defaults(): self
    {
        return new self(self::DEFAULT_LABEL_SIZE, self::DEFAULT_RED);
    }

    /**
     * @param array<string, mixed> $printSettings
     */
    public static function fromArray(array $printSettings): self
    {
        $red = $printSettings['red'] ?? null;
        if (!\is_bool($red)) {
            throw new LabelPrintJobFailedException('Brother QL red mode must be true or false.');
        }

        return new self(self::stringFrom($printSettings, 'labelSize'), $red);
    }

    /**
     * @return list<string>
     */
    public static function allowedLabelSizes(): array
    {
        return self::ALLOWED_LABEL_SIZES;
    }

    /**
     * @return array{labelSize: string, red: bool}
     */
    public function printSettingsData(): array
    {
        return [
            'labelSize' => $this->labelSize,
            'red' => $this->red,
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function stringFrom(array $data, string $key): string
    {
        if (!isset($data[$key])) {
            return '';
        }
        $raw = $data[$key];

        return \is_string($raw) ? $raw : '';
    }
}
