<?php

declare(strict_types=1);

namespace App\Inventory\Application\Controller;

use App\Inventory\Application\Dto\PostInventoryItemRequest;
use App\Inventory\Application\InventoryItemApplicationService;
use App\SharedKernel\Application\ApiIri;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final class PostInventoryItemController extends AbstractController
{
    #[Route(path: '/api/inventory_items', methods: ['POST'])]
    public function __invoke(#[MapRequestPayload] PostInventoryItemRequest $request, InventoryItemApplicationService $inventoryItemSvc): Response
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
        $inventoryItemSvc->createInventoryItem($catalogId, $locationId, $expiration);

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
