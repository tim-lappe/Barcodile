<?php

declare(strict_types=1);

namespace App\Cart\Application\Controller;

use App\Cart\Application\ShoppingCartApplicationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class GetShoppingCartFromProviderController extends AbstractController
{
    #[Route(path: '/api/shopping_carts/providers/{providerId}', methods: ['GET'])]
    public function __invoke(string $providerId, ShoppingCartApplicationService $cartAppSvc): JsonResponse
    {
        return $this->json($cartAppSvc->shoppingCartFromProvider($providerId));
    }
}
