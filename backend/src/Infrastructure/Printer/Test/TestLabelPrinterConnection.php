<?php

declare(strict_types=1);

namespace App\Infrastructure\Printer\Test;

use App\Domain\Printer\Dto\LabelPrinterConnection;
use App\Domain\Printer\Exception\LabelPrintJobFailedException;

final readonly class TestLabelPrinterConnection implements LabelPrinterConnection
{
    public const PRINTER_IDENTIFIER = 'test://logger';

    private function __construct(
        public string $printerIdentifier,
    ) {
        if (self::PRINTER_IDENTIFIER !== $printerIdentifier) {
            throw new LabelPrintJobFailedException('Invalid test printer connection.');
        }
    }

    public static function defaults(): self
    {
        return new self(self::PRINTER_IDENTIFIER);
    }

    /**
     * @param array<string, mixed> $connection
     */
    public static function fromArray(array $connection): self
    {
        return new self(self::stringFrom($connection, 'printerIdentifier'));
    }

    /**
     * @return array{printerIdentifier: string}
     */
    public function connectionData(): array
    {
        return [
            'printerIdentifier' => $this->printerIdentifier,
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
