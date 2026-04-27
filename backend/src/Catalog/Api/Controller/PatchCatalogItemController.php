<?php

declare(strict_types=1);

namespace App\Catalog\Api\Controller;

use App\Catalog\Api\Dto\PatchCatalogItemRequest;
use App\Catalog\Application\CatalogItemApplicationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final class PatchCatalogItemController extends AbstractController
{
    public function __construct(
        private readonly CatalogItemApplicationService $catalogApp,
    ) {
    }

    #[Route(path: '/api/catalog_items/{catalogItemId}', methods: ['PATCH'])]
    public function __invoke(string $catalogItemId, #[MapRequestPayload] PatchCatalogItemRequest $request): Response
    {
        $this->catalogApp->updateCatalogItem($catalogItemId, $request);

        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
