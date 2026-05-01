<?php

declare(strict_types=1);

namespace App\Catalog\Application\Controller;

use App\Catalog\Application\CatalogItemApplicationService;
use App\Catalog\Application\Dto\ListCatalogItemsQuery;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;

final class ListCatalogItemsController extends AbstractController
{
    #[Route(path: '/api/catalog_items', methods: ['GET'])]
    public function __invoke(#[MapQueryString] ?ListCatalogItemsQuery $query, CatalogItemApplicationService $catalogApp): JsonResponse
    {
        $list = $query ?? new ListCatalogItemsQuery();
        $page = max(1, $list->page);
        $per = max(1, $list->itemsPerPage);
        $orderRaw = $list->orderName;
        $order = 'desc' === strtolower($orderRaw) ? 'desc' : 'asc';
        $nameRaw = $list->name;
        $nameFilter = \is_string($nameRaw) && '' !== trim($nameRaw) ? trim($nameRaw) : null;

        return $this->json($catalogApp->listCatalogItems($page, $per, $order, $nameFilter));
    }
}
