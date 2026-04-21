<?php

declare(strict_types=1);

namespace App\Domain\Shared;

trait DomainEventRecorder
{
    /** @var list<object> */
    private array $recordedDomainEvents = [];

    protected function recordDomainEvent(object $event): void
    {
        $this->recordedDomainEvents[] = $event;
    }

    /**
     * @return list<object>
     */
    public function pullRecordedDomainEvents(): array
    {
        $events = $this->recordedDomainEvents;
        $this->recordedDomainEvents = [];

        return $events;
    }
}
