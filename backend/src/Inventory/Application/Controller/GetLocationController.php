<?php

declare(strict_types=1);

namespace App\Inventory\Application\Controller;

use App\Inventory\Application\LocationApplicationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class GetLocationController extends AbstractController
{
    #[Route(path: '/api/locations/{locationId}', methods: ['GET'])]
    public function __invoke(string $locationId, LocationApplicationService $locationApp): JsonResponse
    {
        return $this->json($locationApp->getLocation($locationId));
    }
}
