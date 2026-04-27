<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain;

enum VolumeUnit: string
{
    case Millilitre = 'ml';
    case Litre = 'l';
}
