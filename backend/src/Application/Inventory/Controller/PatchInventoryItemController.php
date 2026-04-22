<?php

declare(strict_types=1);

namespace App\Application\Inventory\Controller;

use App\Application\Inventory\Dto\PatchInventoryItemRequest;
use App\Application\Inventory\InventoryItemApplicationService;
use App\Application\Shared\ApiIri;
use App\Domain\Catalog\Entity\CatalogItemId;
use App\Domain\Inventory\Entity\InventoryItemId;
use App\Domain\Inventory\Entity\LocationId;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final class PatchInventoryItemController extends AbstractController
{
    public function __construct(
        private readonly InventoryItemApplicationService $inventoryItemSvc,
    ) {
    }

    #[Route(path: '/api/inventory_items/{inventoryItemId}', methods: ['PATCH'])]
    public function __invoke(string $inventoryItemId, #[MapRequestPayload] PatchInventoryItemRequest $request): Response
    {
        $catalogId = CatalogItemId::fromString(ApiIri::tailAfterPrefix(ApiIri::PREFIX_CATALOG_ITEM, $request->catalogItem));
        $locationId = null;
        if (null !== $request->location) {
            $locationId = LocationId::fromString(ApiIri::tailAfterPrefix(ApiIri::PREFIX_LOCATION, $request->location));
        }
        $expiration = null;
        if (null !== $request->expirationDate) {
            $expiration = new DateTimeImmutable($request->expirationDate);
        }
        $this->inventoryItemSvc->updateInventoryItem(
            InventoryItemId::fromString($inventoryItemId),
            $catalogId,
            $locationId,
            $expiration,
        );

        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
