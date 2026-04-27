<?php

declare(strict_types=1);

namespace App\Catalog\Api\Dto;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class ListCatalogItemsQuery
{
    public int $page = 1;

    public int $itemsPerPage = 100;

    #[SerializedName('order[name]')]
    public string $orderName = 'asc';

    public ?string $name = null;
}
