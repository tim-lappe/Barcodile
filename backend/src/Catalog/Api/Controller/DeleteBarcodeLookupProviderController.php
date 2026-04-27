<?php

declare(strict_types=1);

namespace App\Catalog\Api\Controller;

use App\Catalog\Application\BarcodeLookupProviderApplicationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DeleteBarcodeLookupProviderController extends AbstractController
{
    public function __construct(
        private readonly BarcodeLookupProviderApplicationService $barcodeProviders,
    ) {
    }

    #[Route(path: '/api/settings/barcode-lookup-providers/{id}', methods: ['DELETE'])]
    public function __invoke(string $id): Response
    {
        $this->barcodeProviders->deleteProvider($id);

        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
