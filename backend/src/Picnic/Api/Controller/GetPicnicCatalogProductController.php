<?php

declare(strict_types=1);

namespace App\Picnic\Api\Controller;

use App\Picnic\Application\PicnicIntegrationApplicationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class GetPicnicCatalogProductController extends AbstractController
{
    public function __construct(
        private readonly PicnicIntegrationApplicationService $picnicApp,
    ) {
    }

    #[Route(path: '/api/settings/picnic/catalog-products/{productId}', methods: ['GET'])]
    public function __invoke(string $productId): JsonResponse
    {
        return $this->json($this->picnicApp->catalogProductSummary($productId));
    }
}
