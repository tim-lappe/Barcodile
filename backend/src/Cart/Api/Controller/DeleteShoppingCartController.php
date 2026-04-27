<?php

declare(strict_types=1);

namespace App\Cart\Api\Controller;

use App\Cart\Application\ShoppingCartApplicationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DeleteShoppingCartController extends AbstractController
{
    public function __construct(
        private readonly ShoppingCartApplicationService $cartAppSvc,
    ) {
    }

    #[Route(path: '/api/shopping_carts/{cartId}', methods: ['DELETE'])]
    public function __invoke(string $cartId): Response
    {
        $this->cartAppSvc->deleteShoppingCart($cartId);

        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
