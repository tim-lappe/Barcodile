<?php

declare(strict_types=1);

namespace App\Domain\Shared;

interface RecordsDomainEvents
{
    /**
     * @return list<object>
     */
    public function pullRecordedDomainEvents(): array;
}
