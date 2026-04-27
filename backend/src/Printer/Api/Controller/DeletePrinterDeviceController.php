<?php

declare(strict_types=1);

namespace App\Printer\Api\Controller;

use App\Printer\Application\PrinterDeviceApplicationService;
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
        requirements: ['printerDeviceId' => '[0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{12}'],
    )]
    public function __invoke(string $printerDeviceId): Response
    {
        $this->printerDeviceApp->deletePrinterDevice($printerDeviceId);

        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
