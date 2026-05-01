<?php

declare(strict_types=1);

namespace App\Inventory\Application\Controller;

use App\Inventory\Application\InventoryItemApplicationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class GetInventoryItemController extends AbstractController
{
    #[Route(path: '/api/inventory_items/{inventoryItemId}', methods: ['GET'])]
    public function __invoke(string $inventoryItemId, InventoryItemApplicationService $inventoryApp): JsonResponse
    {
        return $this->json($inventoryApp->getInventoryItem($inventoryItemId));
    }
}
