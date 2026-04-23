<?php

declare(strict_types=1);

namespace App\Application\Catalog\Controller;

use App\Application\Catalog\CatalogItemApplicationService;
use App\Domain\Shared\Id\CatalogItemId;
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
        return $this->json($this->catalogApp->uploadCatalogItemImage(CatalogItemId::fromString($catalogItemId), $file));
    }
}
