<?php

declare(strict_types=1);

namespace App\Scanner\Api\Controller;

use App\Scanner\Application\ScannerDeviceApplicationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class ListScannerDeviceInputOptionsController extends AbstractController
{
    public function __construct(
        private readonly ScannerDeviceApplicationService $scannerDeviceApp,
    ) {
    }

    #[Route(path: '/api/scanner_devices/input_device_options', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        return $this->json($this->scannerDeviceApp->listInputDeviceOptions());
    }
}
