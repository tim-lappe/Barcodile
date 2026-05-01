<?php

declare(strict_types=1);

namespace App\Scanner\Application\Controller;

use App\Scanner\Application\ScannerDeviceApplicationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DeleteScannerDeviceController extends AbstractController
{
    #[Route(
        path: '/api/scanner_devices/{scannerDeviceId}',
        methods: ['DELETE'],
        requirements: ['scannerDeviceId' => '[0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{12}'],
    )]
    public function __invoke(string $scannerDeviceId, ScannerDeviceApplicationService $scannerDeviceApp): Response
    {
        $scannerDeviceApp->deleteScannerDevice($scannerDeviceId);

        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
