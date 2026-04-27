<?php

declare(strict_types=1);

namespace App\Inventory\Api\Controller;

use App\Inventory\Api\Dto\PatchInventoryItemRequest;
use App\Inventory\Application\InventoryItemApplicationService;
use App\SharedKernel\Application\ApiIri;
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
        $catalogId = ApiIri::tailAfterPrefix(ApiIri::PREFIX_CATALOG_ITEM, $request->catalogItem);
        $locationId = null;
        if (null !== $request->location) {
            $locationId = ApiIri::tailAfterPrefix(ApiIri::PREFIX_LOCATION, $request->location);
        }
        $expiration = null;
        if (null !== $request->expirationDate) {
            $expiration = new DateTimeImmutable($request->expirationDate);
        }
        $this->inventoryItemSvc->updateInventoryItem(
            $inventoryItemId,
            $catalogId,
            $locationId,
            $expiration,
        );

        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
