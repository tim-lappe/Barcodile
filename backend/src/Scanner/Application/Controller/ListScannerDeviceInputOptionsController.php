<?php

declare(strict_types=1);

namespace App\Scanner\Application\Controller;

use App\Scanner\Application\ScannerDeviceApplicationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class ListScannerDeviceInputOptionsController extends AbstractController
{
    #[Route(path: '/api/scanner_devices/input_device_options', methods: ['GET'])]
    public function __invoke(ScannerDeviceApplicationService $scannerDeviceApp): JsonResponse
    {
        return $this->json($scannerDeviceApp->listInputDeviceOptions());
    }
}
