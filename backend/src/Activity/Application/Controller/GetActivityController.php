<?php

declare(strict_types=1);

namespace App\Activity\Application\Controller;

use App\Activity\Application\ActivityApplicationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class GetActivityController extends AbstractController
{
    #[Route(path: '/api/activity', methods: ['GET'])]
    public function __invoke(ActivityApplicationService $activityService): JsonResponse
    {
        return $this->json($activityService->listRecentPersistedDomainEvents());
    }
}
