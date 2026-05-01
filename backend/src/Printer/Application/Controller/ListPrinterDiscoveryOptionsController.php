<?php

declare(strict_types=1);

namespace App\Printer\Application\Controller;

use App\Printer\Application\PrinterDeviceApplicationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;

final class ListPrinterDiscoveryOptionsController extends AbstractController
{
    #[Route(path: '/api/printer_devices/discovery_options', methods: ['GET'])]
    public function __invoke(Request $request, PrinterDeviceApplicationService $printerDeviceApp): JsonResponse
    {
        $driver = $request->query->getString('driver');
        if ('' === $driver) {
            throw new BadRequestHttpException('Query parameter "driver" is required.');
        }

        return $this->json($printerDeviceApp->listDiscoveryOptions($driver));
    }
}
