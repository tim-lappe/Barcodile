<?php

declare(strict_types=1);

namespace App\Application\Printer\Controller;

use App\Application\Printer\PrinterDeviceApplicationService;
use App\Domain\Shared\Id\PrinterDeviceId;
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
        requirements: ['printerDeviceId' => '[0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{12}'],
    )]
    public function __invoke(string $printerDeviceId): JsonResponse
    {
        return $this->json($this->printerDeviceApp->getPrinterDevice(PrinterDeviceId::fromString($printerDeviceId)));
    }
}
