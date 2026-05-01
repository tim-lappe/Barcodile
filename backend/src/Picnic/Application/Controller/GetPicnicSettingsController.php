<?php

declare(strict_types=1);

namespace App\Picnic\Application\Controller;

use App\Picnic\Application\PicnicIntegrationApplicationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class GetPicnicSettingsController extends AbstractController
{
    #[Route(path: '/api/settings/picnic', methods: ['GET'])]
    public function __invoke(PicnicIntegrationApplicationService $picnicApp): JsonResponse
    {
        return $this->json($picnicApp->getSettings());
    }
}
