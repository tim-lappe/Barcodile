<?php

declare(strict_types=1);

namespace App\Catalog\Infrastructure\Doctrine;

use App\SharedKernel\Domain\Id\BarcodeLookupProviderId;
use App\SharedKernel\Infrastructure\Doctrine\Type\AbstractUuidIdDoctrineType;

final class BarcodeLookupProviderIdType extends AbstractUuidIdDoctrineType
{
    public const NAME = 'barcode_lookup_provider_id';

    public function getName(): string
    {
        return self::NAME;
    }

    protected function getIdClass(): string
    {
        return BarcodeLookupProviderId::class;
    }
}
