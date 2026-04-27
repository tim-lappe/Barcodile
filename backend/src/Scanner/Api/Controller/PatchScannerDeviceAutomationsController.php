<?php

declare(strict_types=1);

namespace App\Scanner\Api\Controller;

use App\Scanner\Api\Dto\PatchScannerDeviceAutomationsRequest;
use App\Scanner\Application\ScannerDeviceApplicationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final class PatchScannerDeviceAutomationsController extends AbstractController
{
    public function __construct(
        private readonly ScannerDeviceApplicationService $scannerDeviceApp,
    ) {
    }

    #[Route(
        path: '/api/scanner_devices/{scannerDeviceId}',
        methods: ['PATCH'],
        requirements: ['scannerDeviceId' => '[0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{12}'],
    )]
    public function __invoke(
        string $scannerDeviceId,
        #[MapRequestPayload] PatchScannerDeviceAutomationsRequest $request,
    ): JsonResponse {
        return $this->json($this->scannerDeviceApp->patchScannerDeviceAutomations(
            $scannerDeviceId,
            $request,
        ));
    }
}
