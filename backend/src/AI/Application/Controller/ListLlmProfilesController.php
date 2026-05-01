<?php

declare(strict_types=1);

namespace App\AI\Application\Controller;

use App\AI\Application\LlmProfileApplicationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class ListLlmProfilesController extends AbstractController
{
    #[Route(path: '/api/settings/llm-profiles', methods: ['GET'])]
    public function __invoke(LlmProfileApplicationService $llmProfiles): JsonResponse
    {
        $rows = $llmProfiles->listProfiles();
        $payload = [];
        foreach ($rows as $row) {
            $payload[] = $row->toArray();
        }

        return $this->json($payload);
    }
}
