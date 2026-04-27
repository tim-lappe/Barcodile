<?php

declare(strict_types=1);

namespace App\Application\Cart\Controller;

use App\Application\Cart\ShoppingCartApplicationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class GetShoppingCartController extends AbstractController
{
    public function __construct(
        private readonly ShoppingCartApplicationService $cartAppSvc,
    ) {
    }

    #[Route(path: '/api/shopping_carts/{cartId}', methods: ['GET'])]
    public function __invoke(string $cartId): JsonResponse
    {
        return $this->json($this->cartAppSvc->getShoppingCart($cartId));
    }
}
