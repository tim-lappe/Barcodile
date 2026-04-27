<?php

declare(strict_types=1);

namespace App\Cart\Api\Controller;

use App\Cart\Application\CartProviderIndexApplicationService;
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
