<?php

declare(strict_types=1);

namespace App\AI\Application\Controller;

use App\AI\Application\LlmProfileApplicationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class PostLlmProfileTestController extends AbstractController
{
    #[Route(path: '/api/settings/llm-profiles/{llmProfileId}/test', methods: ['POST'])]
    public function __invoke(string $llmProfileId, LlmProfileApplicationService $llmProfiles): JsonResponse
    {
        return $this->json($llmProfiles->testProfile($llmProfileId)->toArray());
    }
}
