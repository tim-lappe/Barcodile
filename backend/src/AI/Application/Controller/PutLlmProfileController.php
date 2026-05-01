<?php

declare(strict_types=1);

namespace App\AI\Application\Controller;

use App\AI\Application\Dto\PutLlmProfileRequest;
use App\AI\Application\LlmProfileApplicationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final class PutLlmProfileController extends AbstractController
{
    #[Route(path: '/api/settings/llm-profiles/{profileId}', methods: ['PUT'])]
    public function __invoke(string $profileId, #[MapRequestPayload] PutLlmProfileRequest $request, LlmProfileApplicationService $llmProfiles): JsonResponse
    {
        return $this->json($llmProfiles->updateProfile($profileId, $request)->toArray());
    }
}
