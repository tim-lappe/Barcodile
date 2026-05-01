<?php

declare(strict_types=1);

namespace App\Inventory\Application\Controller;

use App\Inventory\Application\LocationApplicationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class ListLocationsController extends AbstractController
{
    #[Route(path: '/api/locations', methods: ['GET'])]
    public function __invoke(LocationApplicationService $locationApp): JsonResponse
    {
        return $this->json($locationApp->listLocations());
    }
}
