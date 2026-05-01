<?php

declare(strict_types=1);

namespace App\AI\Application\Controller;

use App\AI\Application\LlmProfileApplicationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DeleteLlmProfileController extends AbstractController
{
    #[Route(path: '/api/settings/llm-profiles/{llmProfileId}', methods: ['DELETE'])]
    public function __invoke(string $llmProfileId, LlmProfileApplicationService $llmProfiles): Response
    {
        $llmProfiles->deleteProfile($llmProfileId);

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
