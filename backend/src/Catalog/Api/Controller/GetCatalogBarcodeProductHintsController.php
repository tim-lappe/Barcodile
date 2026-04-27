<?php

declare(strict_types=1);

namespace App\Catalog\Api\Controller;

use App\Catalog\Application\BarcodeProductLookupApplicationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class GetCatalogBarcodeProductHintsController extends AbstractController
{
    public function __construct(
        private readonly BarcodeProductLookupApplicationService $barcodeLookup,
    ) {
    }

    #[Route(path: '/api/catalog_items/barcode_product_hints', methods: ['GET'])]
    public function __invoke(Request $request): JsonResponse
    {
        $raw = $request->query->get('barcode');
        $barcode = \is_string($raw) ? $raw : '';

        return $this->json($this->barcodeLookup->hintByBarcode($barcode));
    }
}
