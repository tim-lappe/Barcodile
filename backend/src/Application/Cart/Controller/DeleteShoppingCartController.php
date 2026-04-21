<?php

declare(strict_types=1);

namespace App\Application\Cart\Controller;

use App\Application\Cart\ShoppingCartApplicationService;
use App\Domain\Cart\Entity\ShoppingCartId;
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
        $this->cartAppSvc->deleteShoppingCart(ShoppingCartId::fromString($cartId));

        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
