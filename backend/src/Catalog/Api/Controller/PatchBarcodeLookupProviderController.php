<?php

declare(strict_types=1);

namespace App\Catalog\Api\Controller;

use App\Catalog\Application\BarcodeLookupProviderApplicationService;
use App\Catalog\Application\Dto\PatchBarcodeLookupProviderRequest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final class PatchBarcodeLookupProviderController extends AbstractController
{
    public function __construct(
        private readonly BarcodeLookupProviderApplicationService $barcodeProviders,
    ) {
    }

    #[Route(path: '/api/settings/barcode-lookup-providers/{id}', methods: ['PATCH'])]
    public function __invoke(string $id, #[MapRequestPayload] PatchBarcodeLookupProviderRequest $request): JsonResponse
    {
        return $this->json($this->barcodeProviders->patchProvider($id, $request));
    }
}
