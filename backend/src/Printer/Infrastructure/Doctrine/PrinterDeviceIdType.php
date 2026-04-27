<?php

declare(strict_types=1);

namespace App\Printer\Infrastructure\Doctrine;

use App\SharedKernel\Domain\Id\PrinterDeviceId;
use App\SharedKernel\Infrastructure\Doctrine\Type\AbstractUuidIdDoctrineType;

final class PrinterDeviceIdType extends AbstractUuidIdDoctrineType
{
    public const NAME = 'printer_device_id';

    public function getName(): string
    {
        return self::NAME;
    }

    protected function getIdClass(): string
    {
        return PrinterDeviceId::class;
    }
}
