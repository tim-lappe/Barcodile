<?php

declare(strict_types=1);

namespace App\Application\Printer\Controller;

use App\Application\Printer\PrinterDeviceApplicationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class GetPrinterDeviceController extends AbstractController
{
    public function __construct(
        private readonly PrinterDeviceApplicationService $printerDeviceApp,
    ) {
    }

    #[Route(
        path: '/api/printer_devices/{printerDeviceId}',
        methods: ['GET'],
    )]
    public function __invoke(string $printerDeviceId): JsonResponse
    {
        return $this->json($this->printerDeviceApp->getPrinterDevice($printerDeviceId));
    }
}
