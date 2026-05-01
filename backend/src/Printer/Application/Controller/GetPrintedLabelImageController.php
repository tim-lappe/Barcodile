<?php

declare(strict_types=1);

namespace App\Printer\Application\Controller;

use App\Printer\Application\PrintedLabelApplicationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class GetPrintedLabelImageController extends AbstractController
{
    #[Route(
        path: '/api/printer_devices/{printerDeviceId}/printed_labels/{printedLabelId}.png',
        name: 'api_printer_device_printed_label_image',
        methods: ['GET'],
        requirements: [
            'printerDeviceId' => '[0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{12}',
            'printedLabelId' => '[0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{12}',
        ],
    )]
    public function __invoke(string $printerDeviceId, string $printedLabelId, PrintedLabelApplicationService $printedLabelApp): Response
    {
        return new Response(
            $printedLabelApp->getPrintedLabelImage($printerDeviceId, $printedLabelId),
            Response::HTTP_OK,
            ['Content-Type' => 'image/png'],
        );
    }
}
