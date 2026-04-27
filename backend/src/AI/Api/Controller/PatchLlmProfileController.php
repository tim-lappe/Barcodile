<?php

declare(strict_types=1);

namespace App\AI\Api\Controller;

use App\AI\Application\Dto\PatchLlmProfileRequest;
use App\AI\Application\LlmProfileApplicationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final class PatchLlmProfileController extends AbstractController
{
    public function __construct(
        private readonly LlmProfileApplicationService $llmProfiles,
    ) {
    }

    #[Route(path: '/api/settings/llm-profiles/{id}', methods: ['PATCH'])]
    public function __invoke(string $id, #[MapRequestPayload] PatchLlmProfileRequest $request): JsonResponse
    {
        return $this->json($this->llmProfiles->patchProfile($id, $request)->toArray());
    }
}
