<?php

declare(strict_types=1);

namespace App\Picnic\Domain\Port;

use App\Picnic\Domain\ValueObject\PicnicCatalogSearchUnit;

interface PicnicCatalogSearchPort
{
    /**
     * @return list<PicnicCatalogSearchUnit>
     */
    public function search(string $query): array;
}
