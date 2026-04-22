<?php

declare(strict_types=1);

namespace App\Domain\Shared\Entity;

use App\Domain\Shared\Repository\PersistedDomainEventRepository;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PersistedDomainEventRepository::class)]
#[ORM\Index(name: 'persisted_domain_event_created_at_idx', fields: ['createdAt'])]
#[ORM\Table(name: 'persisted_domain_event')]
class PersistedDomainEvent
{
    #[ORM\Id]
    #[ORM\Column(type: 'persisted_domain_event_id')]
    private PersistedDomainEventId $id;

    /**
     * @var array{eventClass: class-string, data: mixed}
     */
    #[ORM\Column(type: 'json')]
    private array $eventDto;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    /**
     * @param array{eventClass: class-string, data: mixed} $eventDto
     */
    public function __construct(PersistedDomainEventId $id, array $eventDto, DateTimeImmutable $createdAt)
    {
        $this->id = $id;
        $this->eventDto = $eventDto;
        $this->createdAt = $createdAt;
    }

    public function getId(): PersistedDomainEventId
    {
        return $this->id;
    }

    /**
     * @return array{eventClass: class-string, data: mixed}
     */
    public function getEventDto(): array
    {
        return $this->eventDto;
    }

    public function getCreatedAt(): DateTimeInterface
    {
        return $this->createdAt;
    }
}
