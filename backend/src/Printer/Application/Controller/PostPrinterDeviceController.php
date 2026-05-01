<?php

declare(strict_types=1);

namespace App\Printer\Application\Controller;

use App\Printer\Application\Dto\PostPrinterDeviceRequest;
use App\Printer\Application\PrinterDeviceApplicationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final class PostPrinterDeviceController extends AbstractController
{
    #[Route(path: '/api/printer_devices', methods: ['POST'])]
    public function __invoke(#[MapRequestPayload] PostPrinterDeviceRequest $request, PrinterDeviceApplicationService $printerDeviceApp): JsonResponse
    {
        return $this->json($printerDeviceApp->createPrinterDevice($request));
    }
}
