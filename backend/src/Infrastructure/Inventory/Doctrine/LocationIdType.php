<?php

declare(strict_types=1);

namespace App\Infrastructure\Inventory\Doctrine;

use App\Domain\Inventory\Entity\LocationId;
use App\Infrastructure\Shared\Doctrine\Type\AbstractUuidIdDoctrineType;

final class LocationIdType extends AbstractUuidIdDoctrineType
{
    public const NAME = 'location_id';

    public function getName(): string
    {
        return self::NAME;
    }

    protected function getIdClass(): string
    {
        return LocationId::class;
    }
}
