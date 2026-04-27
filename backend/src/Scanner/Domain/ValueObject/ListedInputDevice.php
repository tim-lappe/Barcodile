<?php

declare(strict_types=1);

namespace App\Scanner\Domain\ValueObject;

final readonly class ListedInputDevice
{
    public function __construct(
        public string $deviceIdentifier,
        public string $label,
    ) {
    }
}
