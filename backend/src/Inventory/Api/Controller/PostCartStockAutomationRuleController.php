<?php

declare(strict_types=1);

namespace App\Inventory\Api\Controller;

use App\Inventory\Application\CartStockRuleApplicationService;
use App\Inventory\Application\Dto\PostCartStockAutomationRuleRequest;
use App\SharedKernel\Application\ApiIri;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final class PostCartStockAutomationRuleController extends AbstractController
{
    public function __construct(
        private readonly CartStockRuleApplicationService $cartStockRulesApp,
    ) {
    }

    #[Route(path: '/api/inventory/catalog_items/{catalogItemId}/cart_automation_rules', methods: ['POST'])]
    public function __invoke(string $catalogItemId, #[MapRequestPayload] PostCartStockAutomationRuleRequest $request): JsonResponse
    {
        $shoppingCartId = ApiIri::tailAfterPrefix(ApiIri::PREFIX_SHOPPING_CART, $request->shoppingCart);

        return $this->json($this->cartStockRulesApp->createRule(
            $catalogItemId,
            $shoppingCartId,
            $request->stockBelow,
            $request->addQuantity,
            $request->enabled,
        ));
    }
}
