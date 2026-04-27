<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain;

enum WeightUnit: string
{
    case Gram = 'g';
    case Kilogram = 'kg';
}
