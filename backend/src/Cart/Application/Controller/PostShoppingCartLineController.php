<?php

declare(strict_types=1);

namespace App\Cart\Application\Controller;

use App\Cart\Application\Dto\PostShoppingCartLineRequest;
use App\Cart\Application\ShoppingCartApplicationService;
use App\SharedKernel\Application\ApiIri;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final class PostShoppingCartLineController extends AbstractController
{
    #[Route(path: '/api/shopping_cart_lines', methods: ['POST'])]
    public function __invoke(#[MapRequestPayload] PostShoppingCartLineRequest $request, ShoppingCartApplicationService $cartAppSvc): JsonResponse
    {
        $cartRef = ApiIri::tailAfterPrefix(ApiIri::PREFIX_SHOPPING_CART, $request->shoppingCart);
        $catalogItemId = ApiIri::tailAfterPrefix(ApiIri::PREFIX_CATALOG_ITEM, $request->catalogItem);

        return $this->json($cartAppSvc->createShoppingCartLine(
            $cartRef,
            $catalogItemId,
            $request->quantity,
        ));
    }
}
