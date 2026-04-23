<?php

declare(strict_types=1);

namespace App\Infrastructure\Scanner\Doctrine;

use App\Domain\Shared\Id\ScannerDeviceId;
use App\Infrastructure\Shared\Doctrine\Type\AbstractUuidIdDoctrineType;

final class ScannerDeviceIdType extends AbstractUuidIdDoctrineType
{
    public const NAME = 'scanner_device_id';

    public function getName(): string
    {
        return self::NAME;
    }

    protected function getIdClass(): string
    {
        return ScannerDeviceId::class;
    }
}
