<?php

declare(strict_types=1);

namespace App\Cart\Application\Controller;

use App\Cart\Application\ShoppingCartApplicationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DeleteShoppingCartController extends AbstractController
{
    #[Route(path: '/api/shopping_carts/{cartId}', methods: ['DELETE'])]
    public function __invoke(string $cartId, ShoppingCartApplicationService $cartAppSvc): Response
    {
        $cartAppSvc->deleteShoppingCart($cartId);

        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
