<?php

declare(strict_types=1);

namespace App\Inventory\Api\Controller;

use App\Inventory\Application\InventoryItemApplicationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DeleteInventoryItemController extends AbstractController
{
    public function __construct(
        private readonly InventoryItemApplicationService $inventoryApp,
    ) {
    }

    #[Route(path: '/api/inventory_items/{inventoryItemId}', methods: ['DELETE'])]
    public function __invoke(string $inventoryItemId): Response
    {
        $this->inventoryApp->deleteInventoryItem($inventoryItemId);

        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
