<?php

declare(strict_types=1);

namespace App\Printer\Infrastructure\BrotherQl;

use App\Printer\Domain\Dto\LabelPrinterConnection;

final readonly class BrotherQlPrinterConnection implements LabelPrinterConnection
{
    private const DEFAULT_DISCOVERED_MODEL = 'QL-800';

    private function __construct(
        public string $model,
        public string $printerIdentifier,
        public string $backend,
    ) {
    }

    /**
     * @param array<string, mixed> $connection
     */
    public static function fromArray(array $connection): self
    {
        return new self(
            self::stringFrom($connection, 'model'),
            self::stringFrom($connection, 'printerIdentifier'),
            self::stringFrom($connection, 'backend'),
        );
    }

    public static function discovered(string $printerIdentifier, string $backend): self
    {
        return new self(self::DEFAULT_DISCOVERED_MODEL, $printerIdentifier, $backend);
    }

    /**
     * @return array{model: string, printerIdentifier: string, backend: string}
     */
    public function connectionData(): array
    {
        return [
            'model' => $this->model,
            'printerIdentifier' => $this->printerIdentifier,
            'backend' => $this->backend,
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
