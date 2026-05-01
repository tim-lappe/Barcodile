<?php

declare(strict_types=1);

namespace App\Picnic\Application\Controller;

use App\Picnic\Application\Dto\GetPicnicCatalogSearchQuery;
use App\Picnic\Application\PicnicIntegrationApplicationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;

final class GetPicnicCatalogSearchController extends AbstractController
{
    #[Route(path: '/api/settings/picnic/catalog-search', methods: ['GET'])]
    public function __invoke(#[MapQueryString] ?GetPicnicCatalogSearchQuery $query, PicnicIntegrationApplicationService $picnicApp): JsonResponse
    {
        $queryText = trim(($query ?? new GetPicnicCatalogSearchQuery())->query);

        return $this->json($picnicApp->catalogSearch($queryText));
    }
}
