<?php

declare(strict_types=1);

namespace App\Catalog\Api\Controller;

use App\Catalog\Api\Dto\PostCatalogItemRequest;
use App\Catalog\Application\CatalogItemApplicationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final class PostCatalogItemController extends AbstractController
{
    public function __construct(
        private readonly CatalogItemApplicationService $catalogApp,
    ) {
    }

    #[Route(path: '/api/catalog_items', methods: ['POST'])]
    public function __invoke(#[MapRequestPayload] PostCatalogItemRequest $request): JsonResponse
    {
        return $this->json($this->catalogApp->createCatalogItem($request));
    }
}
