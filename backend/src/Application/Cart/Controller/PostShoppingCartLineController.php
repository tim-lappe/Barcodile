<?php

declare(strict_types=1);

namespace App\Application\Cart\Controller;

use App\Application\Cart\Dto\PostShoppingCartLineRequest;
use App\Application\Cart\ShoppingCartApplicationService;
use App\Application\Shared\ApiIri;
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
