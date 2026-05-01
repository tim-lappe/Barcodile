<?php

declare(strict_types=1);

namespace App\Catalog\Application\Controller;

use App\Catalog\Application\CatalogItemApplicationService;
use App\Catalog\Application\Dto\PostCatalogItemRequest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final class PostCatalogItemController extends AbstractController
{
    #[Route(path: '/api/catalog_items', methods: ['POST'])]
    public function __invoke(#[MapRequestPayload] PostCatalogItemRequest $request, CatalogItemApplicationService $catalogApp): JsonResponse
    {
        return $this->json($catalogApp->createCatalogItem($request));
    }
}
