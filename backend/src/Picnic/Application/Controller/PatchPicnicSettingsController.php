<?php

declare(strict_types=1);

namespace App\Picnic\Application\Controller;

use App\Picnic\Application\Dto\PatchPicnicSettingsRequest;
use App\Picnic\Application\PicnicIntegrationApplicationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final class PatchPicnicSettingsController extends AbstractController
{
    #[Route(path: '/api/settings/picnic', methods: ['PATCH'])]
    public function __invoke(#[MapRequestPayload] PatchPicnicSettingsRequest $request, PicnicIntegrationApplicationService $picnicApp): JsonResponse
    {
        return $this->json($picnicApp->patchSettings($request));
    }
}
