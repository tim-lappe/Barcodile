<?php

declare(strict_types=1);

namespace App\Printer\Api\Controller;

use App\Printer\Application\PrinterDeviceApplicationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class ListPrinterDevicesController extends AbstractController
{
    public function __construct(
        private readonly PrinterDeviceApplicationService $printerDeviceApp,
    ) {
    }

    #[Route(path: '/api/printer_devices', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        return $this->json($this->printerDeviceApp->listPrinterDevices());
    }
}
