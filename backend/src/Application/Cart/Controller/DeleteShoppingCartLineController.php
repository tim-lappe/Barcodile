<?php

declare(strict_types=1);

namespace App\Application\Cart\Controller;

use App\Application\Cart\ShoppingCartApplicationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DeleteShoppingCartLineController extends AbstractController
{
    public function __construct(
        private readonly ShoppingCartApplicationService $cartAppSvc,
    ) {
    }

    #[Route(path: '/api/shopping_cart_lines/{lineId}', methods: ['DELETE'])]
    public function __invoke(string $lineId): Response
    {
        $this->cartAppSvc->deleteShoppingCartLine($lineId);

        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
