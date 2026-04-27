<?php

declare(strict_types=1);

namespace App\Picnic\Infrastructure;

enum PicnicHttpHeaderMode
{
    case Base;
    case WithPicnicAgent;
}
