<?php

declare(strict_types=1);

namespace App\Infrastructure\Printer\Doctrine;

use App\Domain\Shared\Id\PrinterDeviceId;
use App\Infrastructure\Shared\Doctrine\Type\AbstractUuidIdDoctrineType;

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
