<?php

declare(strict_types=1);

namespace App\Application\Location;

use App\Application\Location\Dto\LocationResponse;
use App\Application\Shared\ApiIri;
use App\Domain\Inventory\Entity\Location;
use App\Domain\Inventory\Repository\LocationRepository;
use App\Domain\Shared\Id\LocationId;
use InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class LocationApplicationService
{
    public function __construct(
        private LocationRepository $locationRepository,
    ) {
    }

    /**
     * @return list<LocationResponse>
     */
    public function listLocations(): array
    {
        $out = [];
        foreach ($this->locationRepository->findAllOrderedByName() as $loc) {
            $out[] = $this->map($loc);
        }

        return $out;
    }

    public function getLocation(LocationId $locationId): LocationResponse
    {
        $loc = $this->locationRepository->find($locationId);
        if (!$loc instanceof Location) {
            throw new NotFoundHttpException('Location not found.');
        }

        return $this->map($loc);
    }

    public function createLocation(string $name, ?LocationId $parentId): LocationResponse
    {
        $loc = new Location();
        $loc->changeName($name);
        if (null !== $parentId) {
            $parent = $this->locationRepository->find($parentId);
            if (!$parent instanceof Location) {
                throw new NotFoundHttpException('Parent location not found.');
            }
            $loc->changeParent($parent);
        }
        $this->locationRepository->save($loc);

        return $this->map($loc);
    }

    public function updateLocation(LocationId $locationId, string $name, ?LocationId $parentId): void
    {
        $loc = $this->locationRepository->find($locationId);
        if (!$loc instanceof Location) {
            throw new NotFoundHttpException('Location not found.');
        }
        $loc->changeName($name);
        $this->applyParentChange($loc, $locationId, $parentId);
        $this->locationRepository->save($loc);
    }

    private function applyParentChange(Location $loc, LocationId $locationId, ?LocationId $parentId): void
    {
        if (null === $parentId) {
            $loc->changeParent(null);

            return;
        }
        if ($parentId->equals($locationId)) {
            throw new InvalidArgumentException('Location cannot be its own parent.');
        }
        $parent = $this->locationRepository->find($parentId);
        if (!$parent instanceof Location) {
            throw new NotFoundHttpException('Parent location not found.');
        }
        $loc->changeParent($parent);
    }

    public function deleteLocation(LocationId $locationId): void
    {
        $loc = $this->locationRepository->find($locationId);
        if (!$loc instanceof Location) {
            throw new NotFoundHttpException('Location not found.');
        }
        $this->locationRepository->remove($loc);
    }

    private function map(Location $loc): LocationResponse
    {
        $parentLoc = $loc->getParent();

        return new LocationResponse(
            (string) $loc->getId(),
            $loc->getName(),
            null === $parentLoc ? null : ApiIri::location((string) $parentLoc->getId()),
        );
    }
}
