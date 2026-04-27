<?php

declare(strict_types=1);

namespace App\Inventory\Api\Controller;

use App\Inventory\Application\LocationApplicationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class GetLocationController extends AbstractController
{
    public function __construct(
        private readonly LocationApplicationService $locationApp,
    ) {
    }

    #[Route(path: '/api/locations/{locationId}', methods: ['GET'])]
    public function __invoke(string $locationId): JsonResponse
    {
        return $this->json($this->locationApp->getLocation($locationId));
    }
}
