<?php

declare(strict_types=1);

namespace App\Catalog\Api\Controller;

use App\Catalog\Application\BarcodeLookupProviderApplicationService;
use App\Catalog\Application\Dto\PostBarcodeLookupProviderRequest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final class PostBarcodeLookupProviderController extends AbstractController
{
    public function __construct(
        private readonly BarcodeLookupProviderApplicationService $barcodeProviders,
    ) {
    }

    #[Route(path: '/api/settings/barcode-lookup-providers', methods: ['POST'])]
    public function __invoke(#[MapRequestPayload] PostBarcodeLookupProviderRequest $request): JsonResponse
    {
        return $this->json($this->barcodeProviders->createProvider($request), Response::HTTP_CREATED);
    }
}
