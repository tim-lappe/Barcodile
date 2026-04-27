<?php

declare(strict_types=1);

namespace App\Infrastructure\Printer\Test;

use App\Domain\Printer\Dto\LabelPrintSettings;
use App\Domain\Printer\Exception\LabelPrintJobFailedException;

final readonly class TestLabelPrintSettings implements LabelPrintSettings
{
    public const LABEL_SIZE = 'logger';

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
