<?php

declare(strict_types=1);

namespace App\Catalog\Application\Controller;

use App\Catalog\Application\CatalogItemApplicationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapUploadedFile;
use Symfony\Component\Routing\Attribute\Route;

final class PostCatalogItemImageController extends AbstractController
{
    #[Route(path: '/api/catalog_items/{catalogItemId}/image', methods: ['POST'])]
    public function __invoke(string $catalogItemId, #[MapUploadedFile(name: 'file')] UploadedFile $file, CatalogItemApplicationService $catalogApp): JsonResponse
    {
        return $this->json($catalogApp->uploadCatalogItemImage($catalogItemId, $file));
    }
}
