<?php

declare(strict_types=1);

namespace App\Scanner\Infrastructure\Doctrine;

use App\SharedKernel\Domain\Id\ScannerDeviceId;
use App\SharedKernel\Infrastructure\Doctrine\Type\AbstractUuidIdDoctrineType;

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
