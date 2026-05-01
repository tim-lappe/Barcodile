<?php

declare(strict_types=1);

namespace App\Inventory\Application\Controller;

use App\Inventory\Application\InventoryItemApplicationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class ListInventoryItemsController extends AbstractController
{
    #[Route(path: '/api/inventory_items', methods: ['GET'])]
    public function __invoke(InventoryItemApplicationService $inventoryApp): JsonResponse
    {
        return $this->json($inventoryApp->listInventoryItems());
    }
}
