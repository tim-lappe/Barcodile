<?php

declare(strict_types=1);

namespace App\Printer\Api\Controller;

use App\Printer\Application\PrinterDeviceApplicationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;

final class ListPrinterDiscoveryOptionsController extends AbstractController
{
    public function __construct(
        private readonly PrinterDeviceApplicationService $printerDeviceApp,
    ) {
    }

    #[Route(path: '/api/printer_devices/discovery_options', methods: ['GET'])]
    public function __invoke(Request $request): JsonResponse
    {
        $driver = $request->query->getString('driver');
        if ('' === $driver) {
            throw new BadRequestHttpException('Query parameter "driver" is required.');
        }

        return $this->json($this->printerDeviceApp->listDiscoveryOptions($driver));
    }
}
