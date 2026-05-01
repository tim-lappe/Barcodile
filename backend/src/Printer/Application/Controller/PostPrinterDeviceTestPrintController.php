<?php

declare(strict_types=1);

namespace App\Printer\Application\Controller;

use App\Printer\Application\Dto\TestPrintResponse;
use App\Printer\Application\PrinterDeviceApplicationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class PostPrinterDeviceTestPrintController extends AbstractController
{
    #[Route(
        path: '/api/printer_devices/{printerDeviceId}/test_print',
        methods: ['POST'],
        requirements: ['printerDeviceId' => '[0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{12}'],
    )]
    public function __invoke(string $printerDeviceId, PrinterDeviceApplicationService $printerDeviceApp): JsonResponse
    {
        $printerDeviceApp->printTestLabel($printerDeviceId);

        return $this->json(new TestPrintResponse('queued'));
    }
}
