<?php

declare(strict_types=1);

namespace App\Cart\Api\Controller;

use App\Cart\Api\Dto\PostShoppingCartRequest;
use App\Cart\Application\ShoppingCartApplicationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final class PostShoppingCartController extends AbstractController
{
    public function __construct(
        private readonly ShoppingCartApplicationService $cartAppSvc,
    ) {
    }

    #[Route(path: '/api/shopping_carts', methods: ['POST'])]
    public function __invoke(#[MapRequestPayload] PostShoppingCartRequest $request): JsonResponse
    {
        return $this->json($this->cartAppSvc->createShoppingCart($request->name));
    }
}
