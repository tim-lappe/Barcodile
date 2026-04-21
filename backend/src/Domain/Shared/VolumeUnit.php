<?php

declare(strict_types=1);

namespace App\Domain\Shared;

enum VolumeUnit: string
{
    case Millilitre = 'ml';
    case Litre = 'l';
}
