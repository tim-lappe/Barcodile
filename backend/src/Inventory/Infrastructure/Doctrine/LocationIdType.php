<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Doctrine;

use App\SharedKernel\Domain\Id\LocationId;
use App\SharedKernel\Infrastructure\Doctrine\Type\AbstractUuidIdDoctrineType;

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
