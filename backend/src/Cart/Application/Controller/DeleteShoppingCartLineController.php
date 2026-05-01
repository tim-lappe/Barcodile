<?php

declare(strict_types=1);

namespace App\Cart\Application\Controller;

use App\Cart\Application\ShoppingCartApplicationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DeleteShoppingCartLineController extends AbstractController
{
    #[Route(path: '/api/shopping_cart_lines/{lineId}', methods: ['DELETE'])]
    public function __invoke(string $lineId, ShoppingCartApplicationService $cartAppSvc): Response
    {
        $cartAppSvc->deleteShoppingCartLine($lineId);

        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
