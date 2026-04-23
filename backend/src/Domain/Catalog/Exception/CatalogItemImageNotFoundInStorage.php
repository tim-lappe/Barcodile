<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Exception;

use RuntimeException;

final class CatalogItemImageNotFoundInStorage extends RuntimeException
{
}
