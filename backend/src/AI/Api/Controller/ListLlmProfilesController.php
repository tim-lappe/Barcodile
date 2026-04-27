<?php

declare(strict_types=1);

namespace App\AI\Api\Controller;

use App\AI\Application\LlmProfileApplicationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class ListLlmProfilesController extends AbstractController
{
    public function __construct(
        private readonly LlmProfileApplicationService $llmProfiles,
    ) {
    }

    #[Route(path: '/api/settings/llm-profiles', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        $rows = $this->llmProfiles->listProfiles();
        $payload = [];
        foreach ($rows as $row) {
            $payload[] = $row->toArray();
        }

        return $this->json($payload);
    }
}
