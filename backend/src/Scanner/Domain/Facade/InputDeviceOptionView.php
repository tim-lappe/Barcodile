<?php

declare(strict_types=1);

namespace App\Scanner\Domain\Facade;

final readonly class InputDeviceOptionView
{
    public function __construct(
        public string $deviceIdentifier,
        public string $label,
    ) {
    }
}
