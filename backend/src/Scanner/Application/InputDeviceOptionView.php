<?php

declare(strict_types=1);

namespace App\Scanner\Application;

final readonly class InputDeviceOptionView
{
    public function __construct(
        public string $deviceIdentifier,
        public string $label,
    ) {
    }
}
