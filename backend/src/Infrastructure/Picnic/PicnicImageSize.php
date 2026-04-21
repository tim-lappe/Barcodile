<?php

declare(strict_types=1);

namespace App\Infrastructure\Picnic;

enum PicnicImageSize: string
{
    case Tiny = 'tiny';
    case Small = 'small';
    case Medium = 'medium';
    case Large = 'large';
    case ExtraLarge = 'extra-large';
}
