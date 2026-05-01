<?php

declare(strict_types=1);

namespace App\Cart\Application\Controller;

use App\Cart\Application\ShoppingCartApplicationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class GetShoppingCartController extends AbstractController
{
    #[Route(path: '/api/shopping_carts/{cartId}', methods: ['GET'])]
    public function __invoke(string $cartId, ShoppingCartApplicationService $cartAppSvc): JsonResponse
    {
        return $this->json($cartAppSvc->getShoppingCart($cartId));
    }
}
