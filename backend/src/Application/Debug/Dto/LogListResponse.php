<?php

declare(strict_types=1);

namespace App\Application\Debug\Dto;

final readonly class LogListResponse
{
    /**
     * @param list<LogEntryResponse> $items
     */
    public function __construct(
        public string $source,
        public array $items,
    ) {
    }
}
