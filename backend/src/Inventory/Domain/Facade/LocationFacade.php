<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Facade;

use App\Inventory\Domain\Entity\Location;
use App\Inventory\Domain\Repository\LocationRepository;
use App\SharedKernel\Domain\Id\LocationId;
use InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class LocationFacade
{
    public function __construct(
        private LocationRepository $locationRepository,
    ) {
    }

    /**
     * @return list<LocationView>
     */
    public function listLocations(): array
    {
        return array_map(
            fn (Location $location): LocationView => $this->map($location),
            $this->locationRepository->findAllOrderedByName(),
        );
    }

    public function getLocation(string $locationId): LocationView
    {
        return $this->map($this->mustFind(LocationId::fromString($locationId)));
    }

    public function createLocation(string $name, ?string $parentId): LocationView
    {
        $location = new Location();
        $location->changeName($name);
        if (null !== $parentId) {
            $location->changeParent($this->mustFind(LocationId::fromString($parentId)));
        }
        $this->locationRepository->save($location);

        return $this->map($location);
    }

    public function updateLocation(string $locationId, string $name, ?string $parentId): void
    {
        $locationIdObject = LocationId::fromString($locationId);
        $location = $this->mustFind($locationIdObject);
        $location->changeName($name);
        $this->applyParentChange($location, $locationIdObject, null === $parentId ? null : LocationId::fromString($parentId));
        $this->locationRepository->save($location);
    }

    public function deleteLocation(string $locationId): void
    {
        $this->locationRepository->remove($this->mustFind(LocationId::fromString($locationId)));
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
        $location = $this->locationRepository->find($locationId);
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
