<?php

declare(strict_types=1);

namespace App\Catalog\Application\Controller;

use App\Catalog\Application\CatalogItemApplicationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class CatalogItemPicnicHintController extends AbstractController
{
    #[Route(path: '/api/catalog_items/picnic_product_hints/{productId}', methods: ['GET'])]
    public function __invoke(string $productId, CatalogItemApplicationService $catalogApp): JsonResponse
    {
        return $this->json($catalogApp->picnicProductHint($productId));
    }
}
