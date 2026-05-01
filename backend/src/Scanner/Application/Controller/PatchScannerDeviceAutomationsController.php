<?php

declare(strict_types=1);

namespace App\Scanner\Application\Controller;

use App\Scanner\Application\Dto\PatchScannerDeviceAutomationsRequest;
use App\Scanner\Application\ScannerDeviceApplicationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final class PatchScannerDeviceAutomationsController extends AbstractController
{
    #[Route(
        path: '/api/scanner_devices/{scannerDeviceId}',
        methods: ['PATCH'],
        requirements: ['scannerDeviceId' => '[0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{12}'],
    )]
    public function __invoke(string $scannerDeviceId,
        #[MapRequestPayload] PatchScannerDeviceAutomationsRequest $request, ScannerDeviceApplicationService $scannerDeviceApp): JsonResponse
    {
        return $this->json($scannerDeviceApp->patchScannerDeviceAutomations(
            $scannerDeviceId,
            $request,
        ));
    }
}
