<?php

declare(strict_types=1);

namespace App\Printer\Api\Controller;

use App\Printer\Application\Dto\TestPrintResponse;
use App\Printer\Application\PrinterDeviceApplicationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class PostPrinterDeviceTestPrintController extends AbstractController
{
    public function __construct(
        private readonly PrinterDeviceApplicationService $printerDeviceApp,
    ) {
    }

    #[Route(
        path: '/api/printer_devices/{printerDeviceId}/test_print',
        methods: ['POST']
    )]
    public function __invoke(string $printerDeviceId): JsonResponse
    {
        $this->printerDeviceApp->printTestLabel($printerDeviceId);

        return $this->json(new TestPrintResponse('queued'));
    }
}
