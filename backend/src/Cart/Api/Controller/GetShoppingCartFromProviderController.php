<?php

declare(strict_types=1);

namespace App\Cart\Api\Controller;

use App\Cart\Application\ShoppingCartApplicationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class GetShoppingCartFromProviderController extends AbstractController
{
    public function __construct(
        private readonly ShoppingCartApplicationService $cartAppSvc,
    ) {
    }

    #[Route(path: '/api/shopping_carts/providers/{providerId}', methods: ['GET'])]
    public function __invoke(string $providerId): JsonResponse
    {
        return $this->json($this->cartAppSvc->shoppingCartFromProvider($providerId));
    }
}
