<?php

declare(strict_types=1);

namespace App\Inventory\Application;

use App\Inventory\Api\Dto\LocationResponse;
use App\SharedKernel\Application\ApiIri;

final readonly class LocationResponseMapper
{
    public function fromView(LocationView $location): LocationResponse
    {
        return new LocationResponse(
            $location->resourceId,
            $location->name,
            null === $location->parentId ? null : ApiIri::location($location->parentId),
        );
    }
}
