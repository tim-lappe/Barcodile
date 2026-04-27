<?php

declare(strict_types=1);

namespace App\Inventory\Api\Controller;

use App\Inventory\Application\Dto\PrintInventoryItemLabelRequest;
use App\Inventory\Application\Dto\PrintInventoryItemLabelResponse;
use App\Inventory\Application\InventoryItemApplicationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final class PostInventoryItemPrintLabelController extends AbstractController
{
    public function __construct(
        private readonly InventoryItemApplicationService $inventoryApp,
    ) {
    }

    #[Route(path: '/api/inventory_items/{inventoryItemId}/print_label', methods: ['POST'])]
    public function __invoke(
        string $inventoryItemId,
        #[MapRequestPayload] PrintInventoryItemLabelRequest $request,
    ): JsonResponse {
        $this->inventoryApp->printInventoryItemLabel(
            $inventoryItemId,
            $request->printerDeviceId,
        );

        return $this->json(new PrintInventoryItemLabelResponse('queued'));
    }
}
