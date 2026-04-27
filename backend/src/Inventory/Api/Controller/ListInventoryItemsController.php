<?php

declare(strict_types=1);

namespace App\Inventory\Api\Controller;

use App\Inventory\Application\InventoryItemApplicationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class ListInventoryItemsController extends AbstractController
{
    public function __construct(
        private readonly InventoryItemApplicationService $inventoryApp,
    ) {
    }

    #[Route(path: '/api/inventory_items', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        return $this->json($this->inventoryApp->listInventoryItems());
    }
}
