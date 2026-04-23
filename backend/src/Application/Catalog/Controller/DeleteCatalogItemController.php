<?php

declare(strict_types=1);

namespace App\Application\Catalog\Controller;

use App\Application\Catalog\CatalogItemApplicationService;
use App\Domain\Shared\Id\CatalogItemId;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DeleteCatalogItemController extends AbstractController
{
    public function __construct(
        private readonly CatalogItemApplicationService $catalogApp,
    ) {
    }

    #[Route(path: '/api/catalog_items/{catalogItemId}', methods: ['DELETE'])]
    public function __invoke(string $catalogItemId): Response
    {
        $this->catalogApp->deleteCatalogItem(CatalogItemId::fromString($catalogItemId));

        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
