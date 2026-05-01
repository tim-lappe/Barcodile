<?php

declare(strict_types=1);

namespace App\Printer\Application\Controller;

use App\Printer\Application\PrinterDeviceApplicationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class ListPrinterDriversController extends AbstractController
{
    #[Route(path: '/api/printer_drivers', methods: ['GET'])]
    public function __invoke(PrinterDeviceApplicationService $printerDeviceApp): JsonResponse
    {
        return $this->json($printerDeviceApp->listPrinterDrivers());
    }
}
