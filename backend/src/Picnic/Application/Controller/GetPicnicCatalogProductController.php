<?php

declare(strict_types=1);

namespace App\Picnic\Application\Controller;

use App\Picnic\Application\PicnicIntegrationApplicationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class GetPicnicCatalogProductController extends AbstractController
{
    #[Route(path: '/api/settings/picnic/catalog-products/{productId}', methods: ['GET'])]
    public function __invoke(string $productId, PicnicIntegrationApplicationService $picnicApp): JsonResponse
    {
        return $this->json($picnicApp->catalogProductSummary($productId));
    }
}
