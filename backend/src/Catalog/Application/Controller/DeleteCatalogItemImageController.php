<?php

declare(strict_types=1);

namespace App\Catalog\Application\Controller;

use App\Catalog\Application\CatalogItemApplicationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class DeleteCatalogItemImageController extends AbstractController
{
    #[Route(path: '/api/catalog_items/{catalogItemId}/image', methods: ['DELETE'])]
    public function __invoke(string $catalogItemId, CatalogItemApplicationService $catalogApp): JsonResponse
    {
        return $this->json($catalogApp->deleteCatalogItemImage($catalogItemId));
    }
}
