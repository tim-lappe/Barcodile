<?php

declare(strict_types=1);

namespace App\Cart\Api\Controller;

use App\Cart\Api\Dto\PatchShoppingCartRequest;
use App\Cart\Application\ShoppingCartApplicationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final class PatchShoppingCartController extends AbstractController
{
    public function __construct(
        private readonly ShoppingCartApplicationService $cartAppSvc,
    ) {
    }

    #[Route(path: '/api/shopping_carts/{cartId}', methods: ['PATCH'])]
    public function __invoke(string $cartId, #[MapRequestPayload] PatchShoppingCartRequest $request): Response
    {
        $this->cartAppSvc->updateShoppingCartByRef($cartId, $request->name);

        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
