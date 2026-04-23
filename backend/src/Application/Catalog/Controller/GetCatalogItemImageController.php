<?php

declare(strict_types=1);

namespace App\Application\Catalog\Controller;

use App\Application\Catalog\CatalogItemApplicationService;
use App\Domain\Shared\Id\CatalogItemId;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class GetCatalogItemImageController extends AbstractController
{
    public function __construct(
        private readonly CatalogItemApplicationService $catalogApp,
    ) {
    }

    #[Route(path: '/api/catalog_items/{catalogItemId}/image', methods: ['GET'])]
    public function __invoke(string $catalogItemId): Response
    {
        $result = $this->catalogApp->getCatalogItemImage(CatalogItemId::fromString($catalogItemId));
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
