<?php

declare(strict_types=1);

namespace App\Cart\Application\Controller;

use App\Cart\Application\Dto\PutShoppingCartLineRequest;
use App\Cart\Application\ShoppingCartApplicationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final class PutShoppingCartLineController extends AbstractController
{
    #[Route(path: '/api/shopping_cart_lines/{lineId}', methods: ['PUT'])]
    public function __invoke(string $lineId, #[MapRequestPayload] PutShoppingCartLineRequest $request, ShoppingCartApplicationService $cartAppSvc): Response
    {
        $cartAppSvc->updateShoppingCartLine(
            $lineId,
            $request->quantity,
        );

        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
