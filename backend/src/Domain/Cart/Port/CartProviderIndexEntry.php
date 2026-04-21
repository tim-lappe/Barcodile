<?php

declare(strict_types=1);

namespace App\Domain\Cart\Port;

use DateTimeImmutable;
use DateTimeInterface;
use Symfony\Component\Serializer\Attribute\Context;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;

final readonly class CartProviderIndexEntry
{
    public function __construct(
        public string $providerId,
        public string $name,
        public int $lineCount,
        #[Context([DateTimeNormalizer::FORMAT_KEY => DateTimeInterface::ATOM])]
        public DateTimeImmutable $createdAt,
    ) {
    }
}
