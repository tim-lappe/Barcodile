<?php

declare(strict_types=1);

namespace App\Printer\Api\Controller;

use App\Printer\Application\Dto\TestPrintResponse;
use App\Printer\Application\PrintedLabelApplicationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class PostPrintedLabelResendController extends AbstractController
{
    public function __construct(
        private readonly PrintedLabelApplicationService $printedLabelApp,
    ) {
    }

    #[Route(
        path: '/api/printer_devices/{printerDeviceId}/printed_labels/{printedLabelId}/resend',
        methods: ['POST'],
        requirements: [
            'printerDeviceId' => '[0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{12}',
            'printedLabelId' => '[0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{12}',
        ],
    )]
    public function __invoke(string $printerDeviceId, string $printedLabelId): JsonResponse
    {
        $this->printedLabelApp->resendPrintedLabel($printerDeviceId, $printedLabelId);

        return $this->json(new TestPrintResponse('queued'));
    }
}
