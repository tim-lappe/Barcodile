<?php

declare(strict_types=1);

namespace App\Application\Inventory\Controller;

use App\Application\Inventory\Dto\PostInventoryItemRequest;
use App\Application\Inventory\InventoryItemApplicationService;
use App\Application\Shared\ApiIri;
use App\Domain\Shared\Id\CatalogItemId;
use App\Domain\Shared\Id\LocationId;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final class PostInventoryItemController extends AbstractController
{
    public function __construct(
        private readonly InventoryItemApplicationService $inventoryItemSvc,
    ) {
    }

    #[Route(path: '/api/inventory_items', methods: ['POST'])]
    public function __invoke(#[MapRequestPayload] PostInventoryItemRequest $request): Response
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
        $this->inventoryItemSvc->createInventoryItem($catalogId, $locationId, $expiration);

        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
