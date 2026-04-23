<?php

declare(strict_types=1);

namespace App\Application\Location\Controller;

use App\Application\Location\LocationApplicationService;
use App\Domain\Shared\Id\LocationId;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DeleteLocationController extends AbstractController
{
    public function __construct(
        private readonly LocationApplicationService $locationApp,
    ) {
    }

    #[Route(path: '/api/locations/{locationId}', methods: ['DELETE'])]
    public function __invoke(string $locationId): Response
    {
        $this->locationApp->deleteLocation(LocationId::fromString($locationId));

        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
