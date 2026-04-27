<?php

declare(strict_types=1);

namespace App\Application\Debug\Dto;

use Symfony\Component\Serializer\Attribute\SerializedName;

final readonly class LogEntryResponse
{
    public function __construct(
        #[SerializedName('id')]
        public string $entryIdentifier,
        public int $lineNumber,
        public string $raw,
        public ?string $loggedAt,
        public ?string $channel,
        public ?string $level,
        public ?string $message,
    ) {
    }
}
