<?php

declare(strict_types=1);

namespace App\Catalog\Api\Controller;

use App\Catalog\Application\CatalogItemApplicationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class GetCatalogItemController extends AbstractController
{
    public function __construct(
        private readonly CatalogItemApplicationService $catalogApp,
    ) {
    }

    #[Route(path: '/api/catalog_items/{catalogItemId}', methods: ['GET'])]
    public function __invoke(string $catalogItemId): JsonResponse
    {
        return $this->json($this->catalogApp->getCatalogItem($catalogItemId));
    }
}
