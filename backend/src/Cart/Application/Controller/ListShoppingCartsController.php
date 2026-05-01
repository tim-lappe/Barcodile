<?php

declare(strict_types=1);

namespace App\Cart\Application\Controller;

use App\Cart\Application\ShoppingCartApplicationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class ListShoppingCartsController extends AbstractController
{
    #[Route(path: '/api/shopping_carts', methods: ['GET'])]
    public function __invoke(ShoppingCartApplicationService $cartAppSvc): JsonResponse
    {
        return $this->json($cartAppSvc->listShoppingCarts());
    }
}
