<?php

declare(strict_types=1);

namespace App\Infrastructure\Catalog\Doctrine;

use App\Domain\Catalog\Entity\BarcodeId;
use App\Infrastructure\Shared\Doctrine\Type\AbstractUuidIdDoctrineType;

final class BarcodeIdType extends AbstractUuidIdDoctrineType
{
    public const NAME = 'barcode_id';

    public function getName(): string
    {
        return self::NAME;
    }

    protected function getIdClass(): string
    {
        return BarcodeId::class;
    }
}
