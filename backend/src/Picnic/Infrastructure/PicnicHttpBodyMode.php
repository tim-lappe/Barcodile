<?php

declare(strict_types=1);

namespace App\Picnic\Infrastructure;

enum PicnicHttpBodyMode
{
    case Json;
    case Raw;
}
