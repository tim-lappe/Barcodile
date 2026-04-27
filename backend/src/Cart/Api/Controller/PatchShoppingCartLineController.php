<?php

declare(strict_types=1);

namespace App\Cart\Api\Controller;

use App\Cart\Application\Dto\PatchShoppingCartLineRequest;
use App\Cart\Application\ShoppingCartApplicationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final class PatchShoppingCartLineController extends AbstractController
{
    public function __construct(
        private readonly ShoppingCartApplicationService $cartAppSvc,
    ) {
    }

    #[Route(path: '/api/shopping_cart_lines/{lineId}', methods: ['PATCH'])]
    public function __invoke(string $lineId, #[MapRequestPayload] PatchShoppingCartLineRequest $request): Response
    {
        $this->cartAppSvc->updateShoppingCartLine(
            $lineId,
            $request->quantity,
        );

        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
