<?php

declare(strict_types=1);

namespace App\Infrastructure\Picnic;

enum PicnicHttpHeaderMode
{
    case Base;
    case WithPicnicAgent;
}
