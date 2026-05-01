<?php

declare(strict_types=1);

namespace App\Cart\Application\Controller;

use App\Cart\Application\Dto\PostShoppingCartRequest;
use App\Cart\Application\ShoppingCartApplicationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final class PostShoppingCartController extends AbstractController
{
    #[Route(path: '/api/shopping_carts', methods: ['POST'])]
    public function __invoke(#[MapRequestPayload] PostShoppingCartRequest $request, ShoppingCartApplicationService $cartAppSvc): JsonResponse
    {
        return $this->json($cartAppSvc->createShoppingCart($request->name));
    }
}
