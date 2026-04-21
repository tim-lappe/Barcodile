<?php

declare(strict_types=1);

namespace App\Application\Catalog\Controller;

use App\Application\Catalog\CatalogItemApplicationService;
use App\Application\Catalog\Dto\PatchCatalogItemRequest;
use App\Domain\Catalog\Entity\CatalogItemId;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final class PatchCatalogItemController extends AbstractController
{
    public function __construct(
        private readonly CatalogItemApplicationService $catalogApp,
    ) {
    }

    #[Route(path: '/api/catalog_items/{catalogItemId}', methods: ['PATCH'])]
    public function __invoke(string $catalogItemId, #[MapRequestPayload] PatchCatalogItemRequest $request): Response
    {
        $this->catalogApp->updateCatalogItem(CatalogItemId::fromString($catalogItemId), $request);

        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
