<?php

declare(strict_types=1);

namespace App\Application\Printer\Controller;

use App\Application\Printer\PrinterDeviceApplicationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DeletePrinterDeviceController extends AbstractController
{
    public function __construct(
        private readonly PrinterDeviceApplicationService $printerDeviceApp,
    ) {
    }

    #[Route(
        path: '/api/printer_devices/{printerDeviceId}',
        methods: ['DELETE'],
    )]
    public function __invoke(string $printerDeviceId): Response
    {
        $this->printerDeviceApp->deletePrinterDevice($printerDeviceId);

        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
