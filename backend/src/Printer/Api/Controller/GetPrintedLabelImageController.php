<?php

declare(strict_types=1);

namespace App\Printer\Api\Controller;

use App\Printer\Application\PrintedLabelApplicationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class GetPrintedLabelImageController extends AbstractController
{
    public function __construct(
        private readonly PrintedLabelApplicationService $printedLabelApp,
    ) {
    }

    #[Route(
        path: '/api/printer_devices/{printerDeviceId}/printed_labels/{printedLabelId}.png',
        name: 'api_printer_device_printed_label_image',
        methods: ['GET'],
    )]
    public function __invoke(string $printerDeviceId, string $printedLabelId): Response
    {
        return new Response(
            $this->printedLabelApp->getPrintedLabelImage($printerDeviceId, $printedLabelId),
            Response::HTTP_OK,
            ['Content-Type' => 'image/png'],
        );
    }
}
