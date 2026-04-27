<?php

declare(strict_types=1);

namespace App\Printer\Domain\Facade;

use App\Printer\Domain\Port\LabelPrinterDriver;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class LabelPrinterDriverRegistry
{
    /** @var array<string, LabelPrinterDriver> */
    private array $driversByCode = [];

    /**
     * @param iterable<LabelPrinterDriver> $drivers
     */
    public function __construct(
        #[AutowireIterator(tag: 'barcodile.label_printer_driver')]
        iterable $drivers,
    ) {
        foreach ($drivers as $driver) {
            $this->driversByCode[$driver->driverCode()->value()] = $driver;
        }
    }

    public function get(string $driverCode): LabelPrinterDriver
    {
        if (!isset($this->driversByCode[$driverCode])) {
            throw new NotFoundHttpException('Unknown printer driver.');
        }

        return $this->driversByCode[$driverCode];
    }

    /**
     * @return list<LabelPrinterDriver>
     */
    public function all(): array
    {
        return array_values($this->driversByCode);
    }
}
