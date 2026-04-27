<?php

declare(strict_types=1);

namespace App\Activity\Api\Controller;

use App\Activity\Application\ActivityApplicationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class GetActivityController extends AbstractController
{
    public function __construct(
        private readonly ActivityApplicationService $activityService,
    ) {
    }

    #[Route(path: '/api/activity', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        return $this->json($this->activityService->listRecentPersistedDomainEvents());
    }
}
