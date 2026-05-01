<?php

declare(strict_types=1);

namespace App\Inventory\Application\Controller;

use App\Inventory\Application\InventoryItemApplicationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DeleteInventoryItemController extends AbstractController
{
    #[Route(path: '/api/inventory_items/{inventoryItemId}', methods: ['DELETE'])]
    public function __invoke(string $inventoryItemId, InventoryItemApplicationService $inventoryApp): Response
    {
        $inventoryApp->deleteInventoryItem($inventoryItemId);

        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
