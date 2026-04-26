<?php

declare(strict_types=1);

namespace App\Application\Inventory\Controller;

use App\Application\Inventory\Dto\PrintInventoryItemLabelRequest;
use App\Application\Inventory\Dto\PrintInventoryItemLabelResponse;
use App\Application\Inventory\InventoryItemApplicationService;
use App\Domain\Shared\Id\InventoryItemId;
use App\Domain\Shared\Id\PrinterDeviceId;
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
            InventoryItemId::fromString($inventoryItemId),
            PrinterDeviceId::fromString($request->printerDeviceId),
        );

        return $this->json(new PrintInventoryItemLabelResponse('queued'));
    }
}
