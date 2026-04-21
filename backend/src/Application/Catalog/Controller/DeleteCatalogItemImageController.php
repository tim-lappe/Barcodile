<?php

declare(strict_types=1);

namespace App\Application\Catalog\Controller;

use App\Application\Catalog\CatalogItemApplicationService;
use App\Domain\Catalog\Entity\CatalogItemId;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class DeleteCatalogItemImageController extends AbstractController
{
    public function __construct(
        private readonly CatalogItemApplicationService $catalogApp,
    ) {
    }

    #[Route(path: '/api/catalog_items/{catalogItemId}/image', methods: ['DELETE'])]
    public function __invoke(string $catalogItemId): JsonResponse
    {
        return $this->json($this->catalogApp->deleteCatalogItemImage(CatalogItemId::fromString($catalogItemId)));
    }
}
