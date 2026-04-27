<?php

declare(strict_types=1);

namespace App\Cart\Api\Controller;

use App\Cart\Api\Dto\PostShoppingCartLineRequest;
use App\Cart\Application\ShoppingCartApplicationService;
use App\SharedKernel\Application\ApiIri;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final class PostShoppingCartLineController extends AbstractController
{
    public function __construct(
        private readonly ShoppingCartApplicationService $cartAppSvc,
    ) {
    }

    #[Route(path: '/api/shopping_cart_lines', methods: ['POST'])]
    public function __invoke(#[MapRequestPayload] PostShoppingCartLineRequest $request): JsonResponse
    {
        $cartRef = ApiIri::tailAfterPrefix(ApiIri::PREFIX_SHOPPING_CART, $request->shoppingCart);
        $catalogItemId = ApiIri::tailAfterPrefix(ApiIri::PREFIX_CATALOG_ITEM, $request->catalogItem);

        return $this->json($this->cartAppSvc->createShoppingCartLine(
            $cartRef,
            $catalogItemId,
            $request->quantity,
        ));
    }
}
