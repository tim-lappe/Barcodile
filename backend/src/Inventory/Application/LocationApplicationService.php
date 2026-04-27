<?php

declare(strict_types=1);

namespace App\Inventory\Application;

use App\Inventory\Application\Dto\LocationResponse;
use App\Inventory\Domain\Entity\Location;
use App\Inventory\Domain\Repository\LocationRepository;
use App\SharedKernel\Domain\Id\LocationId;
use InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class LocationApplicationService
{
    public function __construct(
        private LocationRepository $locations,
        private LocationResponseMapper $responseMapper,
    ) {
    }

    /**
     * @return list<LocationResponse>
     */
    public function listLocations(): array
    {
        return array_map(
            fn (Location $location): LocationResponse => $this->responseMapper->fromView($this->map($location)),
            $this->locations->findAllOrderedByName(),
        );
    }

    public function getLocation(string $locationId): LocationResponse
    {
        return $this->responseMapper->fromView($this->map($this->mustFind(LocationId::fromString($locationId))));
    }

    public function createLocation(string $name, ?string $parentId): LocationResponse
    {
        $location = new Location();
        $location->changeName($name);
        if (null !== $parentId) {
            $location->changeParent($this->mustFind(LocationId::fromString($parentId)));
        }
        $this->locations->save($location);

        return $this->responseMapper->fromView($this->map($location));
    }

    public function updateLocation(string $locationId, string $name, ?string $parentId): void
    {
        $locationIdObject = LocationId::fromString($locationId);
        $location = $this->mustFind($locationIdObject);
        $location->changeName($name);
        $this->applyParentChange($location, $locationIdObject, null === $parentId ? null : LocationId::fromString($parentId));
        $this->locations->save($location);
    }

    public function deleteLocation(string $locationId): void
    {
        $this->locations->remove($this->mustFind(LocationId::fromString($locationId)));
    }

    private function applyParentChange(Location $location, LocationId $locationId, ?LocationId $parentId): void
    {
        if (null === $parentId) {
            $location->changeParent(null);

            return;
        }
        if ($parentId->equals($locationId)) {
            throw new InvalidArgumentException('Location cannot be its own parent.');
        }
        $location->changeParent($this->mustFind($parentId));
    }

    private function mustFind(LocationId $locationId): Location
    {
        $location = $this->locations->find($locationId);
        if (!$location instanceof Location) {
            throw new NotFoundHttpException('Location not found.');
        }

        return $location;
    }

    private function map(Location $location): LocationView
    {
        $parent = $location->getParent();

        return new LocationView(
            (string) $location->getId(),
            $location->getName(),
            null === $parent ? null : (string) $parent->getId(),
        );
    }
}
