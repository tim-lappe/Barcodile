<?php

declare(strict_types=1);

namespace App\Inventory\Application;

use App\Catalog\Application\CatalogItemApplicationService;
use App\Inventory\Application\Dto\InventoryItemResponse;
use App\Inventory\Domain\Entity\InventoryItem;
use App\Inventory\Domain\Entity\Location;
use App\Inventory\Domain\Port\LabelPrinter;
use App\Inventory\Domain\Repository\InventoryItemRepository;
use App\Inventory\Domain\Repository\LocationRepository;
use App\SharedKernel\Domain\Id\CatalogItemId;
use App\SharedKernel\Domain\Id\InventoryItemId;
use App\SharedKernel\Domain\Id\LocationId;
use App\SharedKernel\Domain\Label\LabelContent;
use App\SharedKernel\Domain\Label\LabelImageGenerator;
use App\SharedKernel\Domain\Label\LabelSize;
use DateTimeInterface;
use LogicException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class InventoryItemApplicationService
{
    public function __construct(
        private InventoryItemRepository $inventoryItemRepo,
        private LocationRepository $locationRepository,
        private CatalogItemApplicationService $catalog,
        private LabelImageGenerator $labelImageGenerator,
        private LabelPrinter $labelPrinter,
        private LocationResponseMapper $locMapper,
    ) {
    }

    /**
     * @return list<InventoryItemResponse>
     */
    public function listInventoryItems(): array
    {
        return array_map(
            fn (InventoryItemView $item): InventoryItemResponse => $this->toResponse($item),
            array_map(fn (InventoryItem $item): InventoryItemView => $this->mapInventoryItem($item), $this->inventoryItemRepo->findAllOrderedById()),
        );
    }

    public function getInventoryItem(string $inventoryItemId): InventoryItemResponse
    {
        return $this->toResponse($this->mapInventoryItem($this->findInventoryItem($inventoryItemId)));
    }

    public function getInventoryItemLabelImage(string $inventoryItemId): string
    {
        return $this->labelImageGenerator->generate(
            LabelContent::qrCode($this->findInventoryItem($inventoryItemId)->getPublicCode()->value()),
            new LabelSize(62, 21),
        );
    }

    public function printInventoryItemLabel(string $inventoryItemId, string $printerDeviceId): void
    {
        $this->labelPrinter->print(
            LabelContent::qrCode($this->findInventoryItem($inventoryItemId)->getPublicCode()->value()),
            $printerDeviceId,
        );
    }

    public function createInventoryItem(
        string $catalogItemId,
        ?string $locationId,
        ?DateTimeInterface $expirationDate,
    ): void {
        $this->catalog->ensureCatalogItemExists($catalogItemId);
        $item = new InventoryItem();
        $item->assignPublicCode($this->inventoryItemRepo->allocateNextPublicCode());
        $item->changeCatalogItemId(CatalogItemId::fromString($catalogItemId));
        $item->changeLocation(null === $locationId ? null : $this->locationRepositoryFind(LocationId::fromString($locationId)));
        $item->changeExpirationDate($expirationDate);
        $this->inventoryItemRepo->save($item);
    }

    public function updateInventoryItem(
        string $inventoryItemId,
        string $catalogItemId,
        ?string $locationId,
        ?DateTimeInterface $expirationDate,
    ): void {
        $this->catalog->ensureCatalogItemExists($catalogItemId);
        $item = $this->findInventoryItem($inventoryItemId);
        $item->changeCatalogItemId(CatalogItemId::fromString($catalogItemId));
        $item->changeLocation(null === $locationId ? null : $this->locationRepositoryFind(LocationId::fromString($locationId)));
        $item->changeExpirationDate($expirationDate);
        $this->inventoryItemRepo->save($item);
    }

    public function deleteInventoryItem(string $inventoryItemId): void
    {
        $this->inventoryItemRepo->remove($this->findInventoryItem($inventoryItemId));
    }

    private function toResponse(InventoryItemView $item): InventoryItemResponse
    {
        return new InventoryItemResponse(
            $item->resourceId,
            $item->publicCode,
            $this->catalog->getCatalogItem($item->catalogItemId),
            null === $item->location ? null : $this->locMapper->fromView($item->location),
            $item->expirationDate,
            $item->createdAt,
        );
    }

    private function findInventoryItem(string $inventoryItemId): InventoryItem
    {
        $item = $this->inventoryItemRepo->find(InventoryItemId::fromString($inventoryItemId));
        if (!$item instanceof InventoryItem) {
            throw new NotFoundHttpException('Inventory item not found.');
        }

        return $item;
    }

    private function locationRepositoryFind(LocationId $locationId): Location
    {
        $location = $this->locationRepository->find($locationId);
        if (!$location instanceof Location) {
            throw new NotFoundHttpException('Location not found.');
        }

        return $location;
    }

    private function mapInventoryItem(InventoryItem $item): InventoryItemView
    {
        $catalogItemId = $item->getCatalogItemId();
        if (null === $catalogItemId) {
            throw new LogicException('Inventory item without catalog item.');
        }
        $location = $item->getLocation();
        $exp = $item->getExpirationDate();

        return new InventoryItemView(
            (string) $item->getId(),
            $item->getPublicCode()->value(),
            (string) $catalogItemId,
            null === $location ? null : $this->mapLocation($location),
            null === $exp ? null : $exp->format(DateTimeInterface::ATOM),
            $item->getCreatedAt()->format(DateTimeInterface::ATOM),
        );
    }

    private function mapLocation(Location $location): LocationView
    {
        $parent = $location->getParent();

        return new LocationView(
            (string) $location->getId(),
            $location->getName(),
            null === $parent ? null : (string) $parent->getId(),
        );
    }
}
