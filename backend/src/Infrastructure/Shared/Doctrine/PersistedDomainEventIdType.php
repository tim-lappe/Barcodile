<?php

declare(strict_types=1);

namespace App\Infrastructure\Shared\Doctrine;

use App\Domain\Shared\Id\PersistedDomainEventId;
use App\Infrastructure\Shared\Doctrine\Type\AbstractUuidIdDoctrineType;

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
