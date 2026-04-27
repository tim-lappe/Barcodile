<?php

declare(strict_types=1);

namespace App\AI\Api\Controller;

use App\AI\Application\LlmProfileApplicationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DeleteLlmProfileController extends AbstractController
{
    public function __construct(
        private readonly LlmProfileApplicationService $llmProfiles,
    ) {
    }

    #[Route(path: '/api/settings/llm-profiles/{id}', methods: ['DELETE'])]
    public function __invoke(string $id): Response
    {
        $this->llmProfiles->deleteProfile($id);

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
