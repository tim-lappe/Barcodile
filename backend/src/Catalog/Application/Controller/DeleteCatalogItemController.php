<?php

declare(strict_types=1);

namespace App\Catalog\Application\Controller;

use App\Catalog\Application\CatalogItemApplicationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DeleteCatalogItemController extends AbstractController
{
    #[Route(path: '/api/catalog_items/{catalogItemId}', methods: ['DELETE'])]
    public function __invoke(string $catalogItemId, CatalogItemApplicationService $catalogApp): Response
    {
        $catalogApp->deleteCatalogItem($catalogItemId);

        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
