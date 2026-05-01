<?php

declare(strict_types=1);

namespace App\Printer\Application\Controller;

use App\Printer\Application\PrintedLabelApplicationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class ListPrintedLabelsController extends AbstractController
{
    #[Route(
        path: '/api/printer_devices/{printerDeviceId}/printed_labels',
        methods: ['GET'],
        requirements: ['printerDeviceId' => '[0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{12}'],
    )]
    public function __invoke(string $printerDeviceId, PrintedLabelApplicationService $printedLabelApp): JsonResponse
    {
        return $this->json($printedLabelApp->listPrintedLabels($printerDeviceId));
    }
}
