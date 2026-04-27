<?php

declare(strict_types=1);

namespace App\Scanner\Api\Controller;

use App\Scanner\Api\Dto\PostScannerDeviceRequest;
use App\Scanner\Application\ScannerDeviceApplicationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final class PostScannerDeviceController extends AbstractController
{
    public function __construct(
        private readonly ScannerDeviceApplicationService $scannerDeviceApp,
    ) {
    }

    #[Route(path: '/api/scanner_devices', methods: ['POST'])]
    public function __invoke(#[MapRequestPayload] PostScannerDeviceRequest $request): JsonResponse
    {
        return $this->json($this->scannerDeviceApp->createScannerDevice(
            $request->deviceIdentifier,
            $request->name,
        ));
    }
}
