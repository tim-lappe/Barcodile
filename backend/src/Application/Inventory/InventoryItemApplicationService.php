<?php

declare(strict_types=1);

namespace App\Application\Inventory;

use App\Application\Catalog\CatalogItemApplicationService;
use App\Application\Inventory\Dto\InventoryItemResponse;
use App\Application\Inventory\Port\InventoryLabelImageGenerator;
use App\Application\Location\Dto\LocationResponse;
use App\Application\Printer\PrinterDeviceApplicationService;
use App\Application\Shared\ApiIri;
use App\Domain\Catalog\Entity\CatalogItem;
use App\Domain\Inventory\Entity\InventoryItem;
use App\Domain\Inventory\Entity\Location;
use App\Domain\Inventory\Repository\InventoryItemRepository;
use App\Domain\Picnic\Repository\PicnicCatalogItemProductLinkRepository;
use App\Domain\Shared\Id\CatalogItemId;
use App\Domain\Shared\Id\InventoryItemId;
use App\Domain\Shared\Id\LocationId;
use App\Domain\Shared\Id\PrinterDeviceId;
use DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class InventoryItemApplicationService
{
    public function __construct(
        private InventoryItemRepository $inventoryItemRepo,
        private PicnicCatalogItemProductLinkRepository $picnicLinkRepo,
        private CatalogItemApplicationService $catalogItemSvc,
        private EntityManagerInterface $entityManager,
        private InventoryLabelImageGenerator $labelImageGenerator,
        private PrinterDeviceApplicationService $printerDeviceApp,
    ) {
    }

    /**
     * @return list<InventoryItemResponse>
     */
    public function listInventoryItems(): array
    {
        $rows = $this->inventoryItemRepo->findAllOrderedById();
        $catalogIds = [];
        foreach ($rows as $row) {
            $catalogItemId = $row->getCatalogItem()?->getId();
            if (null !== $catalogItemId) {
                $catalogIds[] = $catalogItemId;
            }
        }
        $picnic = $this->picnicLinkRepo->mapProductIdByCatalogItemId($catalogIds);

        return array_map(fn (InventoryItem $inventoryItem) => $this->map($inventoryItem, $picnic), $rows);
    }

    public function getInventoryItem(InventoryItemId $inventoryItemId): InventoryItemResponse
    {
        $item = $this->findInventoryItem($inventoryItemId);
        $catalogItemId = $item->getCatalogItem()?->getId();
        $picnic = null === $catalogItemId ? [] : $this->picnicLinkRepo->mapProductIdByCatalogItemId([$catalogItemId]);

        return $this->map($item, $picnic);
    }

    public function getInventoryItemLabelImage(InventoryItemId $inventoryItemId): string
    {
        return $this->labelImageGenerator->generate($this->findInventoryItem($inventoryItemId)->getPublicCode());
    }

    public function printInventoryItemLabel(InventoryItemId $inventoryItemId, PrinterDeviceId $printerDeviceId): void
    {
        $this->printerDeviceApp->printLabelImage(
            $printerDeviceId,
            $this->getInventoryItemLabelImage($inventoryItemId),
        );
    }

    public function createInventoryItem(
        CatalogItemId $catalogItemId,
        ?LocationId $locationId,
        ?DateTimeInterface $expirationDate,
    ): void {
        $publicCode = $this->inventoryItemRepo->allocateNextPublicCode();
        $catalog = $this->catalogItemRepositoryFind($catalogItemId);
        $loc = null;
        if (null !== $locationId) {
            $loc = $this->locationRepositoryFind($locationId);
        }
        $item = new InventoryItem();
        $item->assignPublicCode($publicCode);
        $item->changeCatalogItem($catalog);
        $item->changeLocation($loc);
        $item->changeExpirationDate($expirationDate);
        $this->inventoryItemRepo->save($item);
    }

    public function updateInventoryItem(
        InventoryItemId $inventoryItemId,
        CatalogItemId $catalogItemId,
        ?LocationId $locationId,
        ?DateTimeInterface $expirationDate,
    ): void {
        $item = $this->findInventoryItem($inventoryItemId);
        $catalog = $this->catalogItemRepositoryFind($catalogItemId);
        $loc = null;
        if (null !== $locationId) {
            $loc = $this->locationRepositoryFind($locationId);
        }
        $item->changeCatalogItem($catalog);
        $item->changeLocation($loc);
        $item->changeExpirationDate($expirationDate);
        $this->inventoryItemRepo->save($item);
    }

    public function deleteInventoryItem(InventoryItemId $inventoryItemId): void
    {
        $item = $this->findInventoryItem($inventoryItemId);
        $this->inventoryItemRepo->remove($item);
    }

    private function findInventoryItem(InventoryItemId $inventoryItemId): InventoryItem
    {
        $item = $this->inventoryItemRepo->find($inventoryItemId);
        if (!$item instanceof InventoryItem) {
            throw new NotFoundHttpException('Inventory item not found.');
        }

        return $item;
    }

    /**
     * @param array<string, string> $picnicMap catalogItemId string => productId
     */
    private function map(InventoryItem $item, array $picnicMap): InventoryItemResponse
    {
        $catalogItem = $this->requireCatalogItem($item);
        $picnicProductId = $picnicMap[(string) $catalogItem->getId()] ?? null;
        $catDto = $this->catalogItemSvc->toCatalogItemResponse($catalogItem, $picnicProductId);
        $locDto = $this->locationResponseOrNull($item->getLocation());
        $exp = $item->getExpirationDate();

        return new InventoryItemResponse(
            (string) $item->getId(),
            $item->getPublicCode(),
            $catDto,
            $locDto,
            null === $exp ? null : $exp->format(DateTimeInterface::ATOM),
            $item->getCreatedAt()->format(DateTimeInterface::ATOM),
        );
    }

    private function requireCatalogItem(InventoryItem $item): CatalogItem
    {
        $catalogItem = $item->getCatalogItem();
        if (null === $catalogItem) {
            throw new LogicException('Inventory item without catalog item.');
        }

        return $catalogItem;
    }

    private function locationResponseOrNull(?Location $loc): ?LocationResponse
    {
        if (!$loc instanceof Location) {
            return null;
        }
        $parentLoc = $loc->getParent();

        return new LocationResponse(
            (string) $loc->getId(),
            $loc->getName(),
            null === $parentLoc ? null : ApiIri::location((string) $parentLoc->getId()),
        );
    }

    private function catalogItemRepositoryFind(CatalogItemId $catalogItemId): CatalogItem
    {
        $entity = $this->entityManager->find(CatalogItem::class, $catalogItemId);
        if (!$entity instanceof CatalogItem) {
            throw new NotFoundHttpException('Catalog item not found.');
        }

        return $entity;
    }

    private function locationRepositoryFind(LocationId $locationId): Location
    {
        $entity = $this->entityManager->find(Location::class, $locationId);
        if (!$entity instanceof Location) {
            throw new NotFoundHttpException('Location not found.');
        }

        return $entity;
    }
}
