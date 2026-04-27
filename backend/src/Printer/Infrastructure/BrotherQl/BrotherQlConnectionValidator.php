<?php

declare(strict_types=1);

namespace App\Printer\Infrastructure\BrotherQl;

use App\Printer\Domain\Exception\LabelPrintJobFailedException;

final class BrotherQlConnectionValidator
{
    /** @var list<string> */
    private const ALLOWED_MODELS = [
        'QL-500', 'QL-550', 'QL-560', 'QL-570', 'QL-580N', 'QL-650TD',
        'QL-700', 'QL-710W', 'QL-720NW', 'QL-800', 'QL-810W', 'QL-820NWB',
        'QL-1050', 'QL-1060N', 'QL-1100', 'QL-1110NWB', 'QL-1115NWB',
    ];

    /** @var list<string> */
    private const ALLOWED_BACKENDS = ['pyusb', 'network', 'linux_kernel'];

    /**
     * @param array<string, mixed> $connection
     */
    public function validate(array $connection): void
    {
        $model = $this->stringFrom($connection, 'model');
        $printerIdentifier = $this->stringFrom($connection, 'printerIdentifier');
        $backend = $this->stringFrom($connection, 'backend');

        $this->requireBrotherModel($model);
        $this->requirePrinterIdentifierPresent($printerIdentifier);
        $this->requireBackendAllowed($backend);
        $this->requireTcpForNetworkBackend($backend, $printerIdentifier);
        $this->requireUsbForPyusbBackend($backend, $printerIdentifier);
        $this->requireFileForKernelBackend($backend, $printerIdentifier);
    }

    private function requireBrotherModel(string $model): void
    {
        if (!\in_array($model, self::ALLOWED_MODELS, true)) {
            throw new LabelPrintJobFailedException('Unsupported or missing Brother QL model.');
        }
    }

    private function requirePrinterIdentifierPresent(string $printerIdentifier): void
    {
        if ('' === $printerIdentifier) {
            throw new LabelPrintJobFailedException('printerIdentifier is required.');
        }
    }

    private function requireBackendAllowed(string $backend): void
    {
        if (!\in_array($backend, self::ALLOWED_BACKENDS, true)) {
            throw new LabelPrintJobFailedException('Invalid backend for Brother QL.');
        }
    }

    private function requireTcpForNetworkBackend(string $backend, string $printerIdentifier): void
    {
        if ('network' !== $backend) {
            return;
        }
        if (!str_starts_with($printerIdentifier, 'tcp://')) {
            throw new LabelPrintJobFailedException('network backend expects printerIdentifier like tcp://host:9100');
        }
    }

    private function requireFileForKernelBackend(string $backend, string $printerIdentifier): void
    {
        if ('linux_kernel' !== $backend) {
            return;
        }
        if (!str_starts_with($printerIdentifier, 'file:///')) {
            throw new LabelPrintJobFailedException('linux_kernel backend expects printerIdentifier like file:///dev/usb/lp1');
        }
    }

    private function requireUsbForPyusbBackend(string $backend, string $printerIdentifier): void
    {
        if ('pyusb' !== $backend) {
            return;
        }
        if (!str_starts_with($printerIdentifier, 'usb://')) {
            throw new LabelPrintJobFailedException('pyusb backend expects printerIdentifier like usb://0x04f9:0x209b');
        }
    }

    /**
     * @param array<string, mixed> $connection
     */
    private function stringFrom(array $connection, string $key): string
    {
        if (!isset($connection[$key])) {
            return '';
        }
        $raw = $connection[$key];

        return \is_string($raw) ? $raw : '';
    }
}
