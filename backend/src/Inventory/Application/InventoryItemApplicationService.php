<?php

declare(strict_types=1);

namespace App\Inventory\Application;

use App\Catalog\Application\CatalogItemApplicationService;
use App\Inventory\Api\Dto\InventoryItemResponse;
use App\Inventory\Api\Dto\LocationResponse;
use App\Inventory\Domain\Facade\InventoryFacade;
use App\Inventory\Domain\Facade\InventoryItemView;
use App\Inventory\Domain\Facade\LocationView;
use App\SharedKernel\Application\ApiIri;
use DateTimeInterface;

final readonly class InventoryItemApplicationService
{
    public function __construct(
        private InventoryFacade $inventory,
        private CatalogItemApplicationService $catalogItems,
    ) {
    }

    /**
     * @return list<InventoryItemResponse>
     */
    public function listInventoryItems(): array
    {
        return array_map(fn (InventoryItemView $item): InventoryItemResponse => $this->map($item), $this->inventory->listInventoryItems());
    }

    public function getInventoryItem(string $inventoryItemId): InventoryItemResponse
    {
        return $this->map($this->inventory->getInventoryItem($inventoryItemId));
    }

    public function getInventoryItemLabelImage(string $inventoryItemId): string
    {
        return $this->inventory->getInventoryItemLabelImage($inventoryItemId);
    }

    public function printInventoryItemLabel(string $inventoryItemId, string $printerDeviceId): void
    {
        $this->inventory->printInventoryItemLabel($inventoryItemId, $printerDeviceId);
    }

    public function createInventoryItem(
        string $catalogItemId,
        ?string $locationId,
        ?DateTimeInterface $expirationDate,
    ): void {
        $this->inventory->createInventoryItem($catalogItemId, $locationId, $expirationDate);
    }

    public function updateInventoryItem(
        string $inventoryItemId,
        string $catalogItemId,
        ?string $locationId,
        ?DateTimeInterface $expirationDate,
    ): void {
        $this->inventory->updateInventoryItem($inventoryItemId, $catalogItemId, $locationId, $expirationDate);
    }

    public function deleteInventoryItem(string $inventoryItemId): void
    {
        $this->inventory->deleteInventoryItem($inventoryItemId);
    }

    private function map(InventoryItemView $item): InventoryItemResponse
    {
        return new InventoryItemResponse(
            $item->resourceId,
            $item->publicCode,
            $this->catalogItems->catalogItemResponse($item->catalogItem),
            null === $item->location ? null : $this->locationResponse($item->location),
            $item->expirationDate,
            $item->createdAt,
        );
    }

    private function locationResponse(LocationView $location): LocationResponse
    {
        return new LocationResponse(
            $location->resourceId,
            $location->name,
            null === $location->parentId ? null : ApiIri::location($location->parentId),
        );
    }
}
