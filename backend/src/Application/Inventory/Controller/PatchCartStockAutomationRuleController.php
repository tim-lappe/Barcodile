<?php

declare(strict_types=1);

namespace App\Application\Inventory\Controller;

use App\Application\Inventory\CartStockRuleApplicationService;
use App\Application\Inventory\Dto\PatchCartStockAutomationRuleRequest;
use App\Domain\Catalog\Entity\CatalogItemId;
use App\Domain\Inventory\Entity\CartStockAutomationRuleId;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final class PatchCartStockAutomationRuleController extends AbstractController
{
    public function __construct(
        private readonly CartStockRuleApplicationService $cartStockRulesApp,
    ) {
    }

    #[Route(path: '/api/inventory/catalog_items/{catalogItemId}/cart_automation_rules/{ruleId}', methods: ['PATCH'])]
    public function __invoke(string $catalogItemId, string $ruleId, #[MapRequestPayload] PatchCartStockAutomationRuleRequest $request): Response
    {
        $this->cartStockRulesApp->patchRule(
            CatalogItemId::fromString($catalogItemId),
            CartStockAutomationRuleId::fromString($ruleId),
            $request,
        );

        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
