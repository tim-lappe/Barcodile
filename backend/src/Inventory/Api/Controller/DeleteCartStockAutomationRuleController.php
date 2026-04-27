<?php

declare(strict_types=1);

namespace App\Inventory\Api\Controller;

use App\Inventory\Application\CartStockRuleApplicationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DeleteCartStockAutomationRuleController extends AbstractController
{
    public function __construct(
        private readonly CartStockRuleApplicationService $cartStockRulesApp,
    ) {
    }

    #[Route(path: '/api/inventory/catalog_items/{catalogItemId}/cart_automation_rules/{ruleId}', methods: ['DELETE'])]
    public function __invoke(string $catalogItemId, string $ruleId): Response
    {
        $this->cartStockRulesApp->deleteRule(
            $catalogItemId,
            $ruleId,
        );

        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
