<?php

declare(strict_types=1);

namespace App\Inventory\Application\Controller;

use App\Inventory\Application\CartStockRuleApplicationService;
use App\Inventory\Application\Dto\PatchCartStockAutomationRuleRequest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final class PatchCartStockAutomationRuleController extends AbstractController
{
    #[Route(path: '/api/inventory/catalog_items/{catalogItemId}/cart_automation_rules/{ruleId}', methods: ['PATCH'])]
    public function __invoke(string $catalogItemId, string $ruleId, #[MapRequestPayload] PatchCartStockAutomationRuleRequest $request, CartStockRuleApplicationService $cartStockRulesApp): Response
    {
        $cartStockRulesApp->patchRule(
            $catalogItemId,
            $ruleId,
            $request,
        );

        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
