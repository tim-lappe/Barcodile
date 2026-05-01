<?php

declare(strict_types=1);

namespace App\Catalog\Application\Controller;

use App\Catalog\Application\CatalogItemApplicationService;
use App\Catalog\Application\Dto\PatchCatalogItemRequest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final class PatchCatalogItemController extends AbstractController
{
    #[Route(path: '/api/catalog_items/{catalogItemId}', methods: ['PATCH'])]
    public function __invoke(string $catalogItemId, #[MapRequestPayload] PatchCatalogItemRequest $request, CatalogItemApplicationService $catalogApp): Response
    {
        $catalogApp->updateCatalogItem($catalogItemId, $request);

        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
