<?php

declare(strict_types=1);

namespace App\Scanner\Api\Dto;

final readonly class InputDeviceOptionResponse
{
    public function __construct(
        public string $deviceIdentifier,
        public string $label,
    ) {
    }
}
