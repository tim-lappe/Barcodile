<?php

declare(strict_types=1);

namespace App\Catalog\Api\Controller;

use App\Catalog\Application\CatalogItemApplicationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapUploadedFile;
use Symfony\Component\Routing\Attribute\Route;

final class PostCatalogItemImageController extends AbstractController
{
    public function __construct(
        private readonly CatalogItemApplicationService $catalogApp,
    ) {
    }

    #[Route(path: '/api/catalog_items/{catalogItemId}/image', methods: ['POST'])]
    public function __invoke(string $catalogItemId, #[MapUploadedFile(name: 'file')] UploadedFile $file): JsonResponse
    {
        return $this->json($this->catalogApp->uploadCatalogItemImage($catalogItemId, $file));
    }
}
