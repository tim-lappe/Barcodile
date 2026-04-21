<?php

declare(strict_types=1);

namespace App\Infrastructure\Picnic;

enum PicnicHttpBodyMode
{
    case Json;
    case Raw;
}
