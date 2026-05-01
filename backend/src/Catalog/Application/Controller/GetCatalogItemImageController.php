<?php

declare(strict_types=1);

namespace App\Catalog\Application\Controller;

use App\Catalog\Application\CatalogItemApplicationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class GetCatalogItemImageController extends AbstractController
{
    #[Route(path: '/api/catalog_items/{catalogItemId}/image', methods: ['GET'])]
    public function __invoke(string $catalogItemId, CatalogItemApplicationService $catalogApp): Response
    {
        $result = $catalogApp->getCatalogItemImage($catalogItemId);
        $headers = [
            'Content-Type' => $result->contentType,
            'Cache-Control' => 'public, max-age=3600',
        ];
        if (null !== $result->eTag) {
            $headers['ETag'] = '"'.$result->eTag.'"';
        }

        return new Response($result->body, Response::HTTP_OK, $headers);
    }
}
