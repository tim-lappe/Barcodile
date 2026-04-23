<?php

declare(strict_types=1);

namespace App\Application\Scanner\Controller;

use App\Application\Scanner\ScannerDeviceApplicationService;
use App\Domain\Shared\Id\ScannerDeviceId;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DeleteScannerDeviceController extends AbstractController
{
    public function __construct(
        private readonly ScannerDeviceApplicationService $scannerDeviceApp,
    ) {
    }

    #[Route(
        path: '/api/scanner_devices/{scannerDeviceId}',
        methods: ['DELETE'],
        requirements: ['scannerDeviceId' => '[0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{12}'],
    )]
    public function __invoke(string $scannerDeviceId): Response
    {
        $this->scannerDeviceApp->deleteScannerDevice(ScannerDeviceId::fromString($scannerDeviceId));

        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
