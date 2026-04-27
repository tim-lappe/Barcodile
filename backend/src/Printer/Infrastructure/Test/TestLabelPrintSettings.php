<?php

declare(strict_types=1);

namespace App\Printer\Infrastructure\Test;

use App\Printer\Domain\Dto\LabelPrintSettings;
use App\Printer\Domain\Exception\LabelPrintJobFailedException;
use App\SharedKernel\Domain\Label\LabelSize;

final readonly class TestLabelPrintSettings implements LabelPrintSettings
{
    public const LABEL_SIZE = 'logger';
    public const LABEL_WIDTH_MM = 62;
    public const LABEL_HEIGHT_MM = 21;

    private function __construct(
        public string $labelSize,
        public bool $red,
    ) {
        if (self::LABEL_SIZE !== $labelSize) {
            throw new LabelPrintJobFailedException('Invalid test printer label size.');
        }
        if (false !== $red) {
            throw new LabelPrintJobFailedException('Invalid test printer color mode.');
        }
    }

    public static function defaults(): self
    {
        return new self(self::LABEL_SIZE, false);
    }

    public static function labelSize(): LabelSize
    {
        return new LabelSize(self::LABEL_WIDTH_MM, self::LABEL_HEIGHT_MM);
    }

    /**
     * @param array<string, mixed> $printSettings
     */
    public static function fromArray(array $printSettings): self
    {
        $red = $printSettings['red'] ?? null;
        if (!\is_bool($red)) {
            throw new LabelPrintJobFailedException('Invalid test printer color mode.');
        }

        return new self(self::stringFrom($printSettings, 'labelSize'), $red);
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
