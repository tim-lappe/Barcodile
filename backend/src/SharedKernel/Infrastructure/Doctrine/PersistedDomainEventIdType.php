<?php

declare(strict_types=1);

namespace App\SharedKernel\Infrastructure\Doctrine;

use App\SharedKernel\Domain\Id\PersistedDomainEventId;
use App\SharedKernel\Infrastructure\Doctrine\Type\AbstractUuidIdDoctrineType;

final class PersistedDomainEventIdType extends AbstractUuidIdDoctrineType
{
    public const NAME = 'persisted_domain_event_id';

    public function getName(): string
    {
        return self::NAME;
    }

    protected function getIdClass(): string
    {
        return PersistedDomainEventId::class;
    }
}
