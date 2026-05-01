<?php

declare(strict_types=1);

namespace App\Inventory\Application\Controller;

use App\Inventory\Application\CartStockRuleApplicationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class ListCartStockAutomationRulesController extends AbstractController
{
    #[Route(path: '/api/inventory/catalog_items/{catalogItemId}/cart_automation_rules', methods: ['GET'])]
    public function __invoke(string $catalogItemId, CartStockRuleApplicationService $cartStockRulesApp): JsonResponse
    {
        return $this->json($cartStockRulesApp->listRules($catalogItemId));
    }
}
