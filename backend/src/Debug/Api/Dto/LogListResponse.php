<?php

declare(strict_types=1);

namespace App\Debug\Api\Dto;

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
