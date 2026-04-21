<?php

declare(strict_types=1);

namespace App\Application\Scanner\Dto;

final readonly class InputDeviceOptionResponse
{
    public function __construct(
        public string $deviceIdentifier,
        public string $label,
    ) {
    }
}
