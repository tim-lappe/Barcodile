<?php

declare(strict_types=1);

namespace App\AI\Api\Controller;

use App\AI\Application\LlmProfileApplicationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class PostLlmProfileTestController extends AbstractController
{
    public function __construct(
        private readonly LlmProfileApplicationService $llmProfiles,
    ) {
    }

    #[Route(path: '/api/settings/llm-profiles/{id}/test', methods: ['POST'])]
    public function __invoke(string $id): JsonResponse
    {
        return $this->json($this->llmProfiles->testProfile($id)->toArray());
    }
}
