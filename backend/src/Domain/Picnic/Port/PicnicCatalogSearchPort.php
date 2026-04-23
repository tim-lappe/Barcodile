<?php

declare(strict_types=1);

namespace App\Domain\Picnic\Port;

use App\Domain\Picnic\ValueObject\PicnicCatalogSearchUnit;

interface PicnicCatalogSearchPort
{
    /**
     * @return list<PicnicCatalogSearchUnit>
     */
    public function search(string $query): array;
}
