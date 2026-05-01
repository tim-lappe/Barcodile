<?php

declare(strict_types=1);

namespace App\Printer\Application\Controller;

use App\Printer\Application\PrinterDeviceApplicationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class GetPrinterDeviceController extends AbstractController
{
    #[Route(
        path: '/api/printer_devices/{printerDeviceId}',
        methods: ['GET'],
        requirements: ['printerDeviceId' => '[0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{12}'],
    )]
    public function __invoke(string $printerDeviceId, PrinterDeviceApplicationService $printerDeviceApp): JsonResponse
    {
        return $this->json($printerDeviceApp->getPrinterDevice($printerDeviceId));
    }
}
