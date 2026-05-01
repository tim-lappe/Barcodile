<?php

declare(strict_types=1);

namespace App\Scanner\Application\Controller;

use App\Scanner\Application\Dto\PostScannerDeviceRequest;
use App\Scanner\Application\ScannerDeviceApplicationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final class PostScannerDeviceController extends AbstractController
{
    #[Route(path: '/api/scanner_devices', methods: ['POST'])]
    public function __invoke(#[MapRequestPayload] PostScannerDeviceRequest $request, ScannerDeviceApplicationService $scannerDeviceApp): JsonResponse
    {
        return $this->json($scannerDeviceApp->createScannerDevice(
            $request->deviceIdentifier,
            $request->name,
        ));
    }
}
