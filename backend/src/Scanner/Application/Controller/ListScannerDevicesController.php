<?php

declare(strict_types=1);

namespace App\Scanner\Application\Controller;

use App\Scanner\Application\ScannerDeviceApplicationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class ListScannerDevicesController extends AbstractController
{
    #[Route(path: '/api/scanner_devices', methods: ['GET'])]
    public function __invoke(ScannerDeviceApplicationService $scannerDeviceApp): JsonResponse
    {
        return $this->json($scannerDeviceApp->listScannerDevices());
    }
}
