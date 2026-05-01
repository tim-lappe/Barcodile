<?php

declare(strict_types=1);

namespace App\Inventory\Application\Controller;

use App\Inventory\Application\Dto\PrintInventoryItemLabelRequest;
use App\Inventory\Application\Dto\PrintInventoryItemLabelResponse;
use App\Inventory\Application\InventoryItemApplicationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final class PostInventoryItemPrintLabelController extends AbstractController
{
    #[Route(path: '/api/inventory_items/{inventoryItemId}/print_label', methods: ['POST'])]
    public function __invoke(string $inventoryItemId,
        #[MapRequestPayload] PrintInventoryItemLabelRequest $request, InventoryItemApplicationService $inventoryApp): JsonResponse
    {
        $inventoryApp->printInventoryItemLabel(
            $inventoryItemId,
            $request->printerDeviceId,
        );

        return $this->json(new PrintInventoryItemLabelResponse('queued'));
    }
}
