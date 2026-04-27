<?php

declare(strict_types=1);

namespace App\Inventory\Application;

use App\Inventory\Api\Dto\LocationResponse;
use App\Inventory\Domain\Facade\LocationFacade;
use App\Inventory\Domain\Facade\LocationView;
use App\SharedKernel\Application\ApiIri;

final readonly class LocationApplicationService
{
    public function __construct(
        private LocationFacade $locations,
    ) {
    }

    /**
     * @return list<LocationResponse>
     */
    public function listLocations(): array
    {
        return array_map(fn (LocationView $location): LocationResponse => $this->map($location), $this->locations->listLocations());
    }

    public function getLocation(string $locationId): LocationResponse
    {
        return $this->map($this->locations->getLocation($locationId));
    }

    public function createLocation(string $name, ?string $parentId): LocationResponse
    {
        return $this->map($this->locations->createLocation($name, $parentId));
    }

    public function updateLocation(string $locationId, string $name, ?string $parentId): void
    {
        $this->locations->updateLocation($locationId, $name, $parentId);
    }

    public function deleteLocation(string $locationId): void
    {
        $this->locations->deleteLocation($locationId);
    }

    private function map(LocationView $location): LocationResponse
    {
        return new LocationResponse(
            $location->resourceId,
            $location->name,
            null === $location->parentId ? null : ApiIri::location($location->parentId),
        );
    }
}
