<?php

declare(strict_types=1);

namespace App\Application\Scanner\Controller;

use App\Application\Scanner\ScannerDeviceApplicationService;
use App\Domain\Shared\Id\ScannerDeviceId;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class GetScannerDeviceController extends AbstractController
{
    public function __construct(
        private readonly ScannerDeviceApplicationService $scannerDeviceApp,
    ) {
    }

    #[Route(
        path: '/api/scanner_devices/{scannerDeviceId}',
        methods: ['GET'],
        requirements: ['scannerDeviceId' => '[0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{12}'],
    )]
    public function __invoke(string $scannerDeviceId): JsonResponse
    {
        return $this->json($this->scannerDeviceApp->getScannerDevice(ScannerDeviceId::fromString($scannerDeviceId)));
    }
}
