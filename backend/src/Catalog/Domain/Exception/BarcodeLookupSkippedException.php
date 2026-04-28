<?php

declare(strict_types=1);

namespace App\Catalog\Domain\Exception;

use RuntimeException;

final class BarcodeLookupSkippedException extends RuntimeException
{
}
