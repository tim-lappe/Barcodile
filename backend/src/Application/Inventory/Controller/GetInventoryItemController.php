<?php

declare(strict_types=1);

namespace App\Application\Inventory\Controller;

use App\Application\Inventory\InventoryItemApplicationService;
use App\Domain\Shared\Id\InventoryItemId;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class GetInventoryItemController extends AbstractController
{
    public function __construct(
        private readonly InventoryItemApplicationService $inventoryApp,
    ) {
    }

    #[Route(path: '/api/inventory_items/{inventoryItemId}', methods: ['GET'])]
    public function __invoke(string $inventoryItemId): JsonResponse
    {
        return $this->json($this->inventoryApp->getInventoryItem(InventoryItemId::fromString($inventoryItemId)));
    }
}
