<?php

declare(strict_types=1);

namespace App\Catalog\Api\Controller;

use App\Catalog\Application\BarcodeLookupProviderApplicationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class ListBarcodeLookupProvidersController extends AbstractController
{
    public function __construct(
        private readonly BarcodeLookupProviderApplicationService $barcodeProviders,
    ) {
    }

    #[Route(path: '/api/settings/barcode-lookup-providers', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        return $this->json($this->barcodeProviders->listProviders());
    }
}
