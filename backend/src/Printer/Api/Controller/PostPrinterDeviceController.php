<?php

declare(strict_types=1);

namespace App\Printer\Api\Controller;

use App\Printer\Application\Dto\PostPrinterDeviceRequest;
use App\Printer\Application\PrinterDeviceApplicationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final class PostPrinterDeviceController extends AbstractController
{
    public function __construct(
        private readonly PrinterDeviceApplicationService $printerDeviceApp,
    ) {
    }

    #[Route(path: '/api/printer_devices', methods: ['POST'])]
    public function __invoke(#[MapRequestPayload] PostPrinterDeviceRequest $request): JsonResponse
    {
        return $this->json($this->printerDeviceApp->createPrinterDevice($request));
    }
}
