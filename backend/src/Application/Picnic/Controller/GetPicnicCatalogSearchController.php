<?php

declare(strict_types=1);

namespace App\Application\Picnic\Controller;

use App\Application\Picnic\Dto\GetPicnicCatalogSearchQuery;
use App\Application\Picnic\PicnicIntegrationApplicationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;

final class GetPicnicCatalogSearchController extends AbstractController
{
    public function __construct(
        private readonly PicnicIntegrationApplicationService $picnicApp,
    ) {
    }

    #[Route(path: '/api/settings/picnic/catalog-search', methods: ['GET'])]
    public function __invoke(#[MapQueryString] ?GetPicnicCatalogSearchQuery $query): JsonResponse
    {
        $queryText = trim(($query ?? new GetPicnicCatalogSearchQuery())->query);

        return $this->json($this->picnicApp->catalogSearch($queryText));
    }
}
