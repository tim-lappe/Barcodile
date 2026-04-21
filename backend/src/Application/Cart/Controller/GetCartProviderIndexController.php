<?php

declare(strict_types=1);

namespace App\Application\Cart\Controller;

use App\Application\Cart\CartProviderIndexApplicationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class GetCartProviderIndexController extends AbstractController
{
    public function __construct(
        private readonly CartProviderIndexApplicationService $cartProviderIndex,
    ) {
    }

    #[Route(path: '/api/shopping_carts/providers', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        return $this->json($this->cartProviderIndex->index());
    }
}
