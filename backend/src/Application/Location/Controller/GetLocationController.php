<?php

declare(strict_types=1);

namespace App\Application\Location\Controller;

use App\Application\Location\LocationApplicationService;
use App\Domain\Inventory\Entity\LocationId;
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
        return $this->json($this->locationApp->getLocation(LocationId::fromString($locationId)));
    }
}
