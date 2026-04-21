<?php

declare(strict_types=1);

namespace App\Application\Inventory\Controller;

use App\Application\Inventory\CartStockRuleApplicationService;
use App\Domain\Catalog\Entity\CatalogItemId;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class ListCartStockAutomationRulesController extends AbstractController
{
    public function __construct(
        private readonly CartStockRuleApplicationService $cartStockRulesApp,
    ) {
    }

    #[Route(path: '/api/inventory/catalog_items/{catalogItemId}/cart_automation_rules', methods: ['GET'])]
    public function __invoke(string $catalogItemId): JsonResponse
    {
        return $this->json($this->cartStockRulesApp->listRules(CatalogItemId::fromString($catalogItemId)));
    }
}
