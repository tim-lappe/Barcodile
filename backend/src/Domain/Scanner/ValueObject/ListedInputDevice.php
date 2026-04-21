<?php

declare(strict_types=1);

namespace App\Domain\Scanner\ValueObject;

final readonly class ListedInputDevice
{
    public function __construct(
        public string $deviceIdentifier,
        public string $label,
    ) {
    }
}
