<?php

declare(strict_types=1);

namespace App\Catalog\Api\Controller;

use App\Catalog\Application\CatalogItemApplicationService;
use App\Catalog\Application\Dto\PostBarcodeCatalogLookupRequest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final class PostCreateCatalogItemFromBarcodeController extends AbstractController
{
    public function __construct(
        private readonly CatalogItemApplicationService $catalogApp,
    ) {
    }

    #[Route(path: '/api/catalog_items/from_barcode', methods: ['POST'])]
    public function __invoke(#[MapRequestPayload] PostBarcodeCatalogLookupRequest $request): JsonResponse
    {
        return $this->json($this->catalogApp->createCatalogItemFromBarcode($request));
    }
}
