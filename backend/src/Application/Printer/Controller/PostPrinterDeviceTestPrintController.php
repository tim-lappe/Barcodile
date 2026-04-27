<?php

declare(strict_types=1);

namespace App\Application\Printer\Controller;

use App\Application\Printer\Dto\TestPrintResponse;
use App\Application\Printer\PrinterDeviceApplicationService;
use App\Domain\Shared\Id\PrinterDeviceId;
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
        $this->printerDeviceApp->printTestLabel(PrinterDeviceId::fromString($printerDeviceId));

        return $this->json(new TestPrintResponse('queued'));
    }
}
