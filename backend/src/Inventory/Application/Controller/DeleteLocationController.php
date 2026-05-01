<?php

declare(strict_types=1);

namespace App\Inventory\Application\Controller;

use App\Inventory\Application\LocationApplicationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DeleteLocationController extends AbstractController
{
    #[Route(path: '/api/locations/{locationId}', methods: ['DELETE'])]
    public function __invoke(string $locationId, LocationApplicationService $locationApp): Response
    {
        $locationApp->deleteLocation($locationId);

        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
