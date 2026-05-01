<?php

declare(strict_types=1);

namespace App\Cart\Application\Controller;

use App\Cart\Application\ShoppingCartApplicationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class GetCartProviderIndexController extends AbstractController
{
    #[Route(path: '/api/shopping_carts/providers', methods: ['GET'])]
    public function __invoke(ShoppingCartApplicationService $cartApp): JsonResponse
    {
        return $this->json($cartApp->providerIndex());
    }
}
