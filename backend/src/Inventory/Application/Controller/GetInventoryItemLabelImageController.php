<?php

declare(strict_types=1);

namespace App\Inventory\Application\Controller;

use App\Inventory\Application\InventoryItemApplicationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class GetInventoryItemLabelImageController extends AbstractController
{
    #[Route(path: '/api/inventory_items/{inventoryItemId}/label_image', methods: ['GET'])]
    public function __invoke(string $inventoryItemId, InventoryItemApplicationService $inventoryApp): Response
    {
        return new Response(
            $inventoryApp->getInventoryItemLabelImage($inventoryItemId),
            Response::HTTP_OK,
            [
                'Content-Type' => 'image/png',
                'Cache-Control' => 'no-store',
            ],
        );
    }
}
