<?php

declare(strict_types=1);

namespace App\Application\Scanner\Controller;

use App\Application\Scanner\ScannerDeviceApplicationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class ListScannerDevicesController extends AbstractController
{
    public function __construct(
        private readonly ScannerDeviceApplicationService $scannerDeviceApp,
    ) {
    }

    #[Route(path: '/api/scanner_devices', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        return $this->json($this->scannerDeviceApp->listScannerDevices());
    }
}
