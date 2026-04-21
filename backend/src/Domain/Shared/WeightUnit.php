<?php

declare(strict_types=1);

namespace App\Domain\Shared;

enum WeightUnit: string
{
    case Gram = 'g';
    case Kilogram = 'kg';
}
