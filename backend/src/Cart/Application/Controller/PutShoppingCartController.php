<?php

declare(strict_types=1);

namespace App\Cart\Application\Controller;

use App\Cart\Application\Dto\PutShoppingCartRequest;
use App\Cart\Application\ShoppingCartApplicationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final class PutShoppingCartController extends AbstractController
{
    #[Route(path: '/api/shopping_carts/{cartId}', methods: ['PUT'])]
    public function __invoke(string $cartId, #[MapRequestPayload] PutShoppingCartRequest $request, ShoppingCartApplicationService $cartAppSvc): Response
    {
        $cartAppSvc->updateShoppingCartByRef($cartId, $request->name);

        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
