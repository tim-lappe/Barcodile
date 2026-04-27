<?php

declare(strict_types=1);

namespace App\AI\Api\Controller;

use App\AI\Application\Dto\PostLlmProfileRequest;
use App\AI\Application\LlmProfileApplicationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final class PostLlmProfileController extends AbstractController
{
    public function __construct(
        private readonly LlmProfileApplicationService $llmProfiles,
    ) {
    }

    #[Route(path: '/api/settings/llm-profiles', methods: ['POST'])]
    public function __invoke(#[MapRequestPayload] PostLlmProfileRequest $request): JsonResponse
    {
        return $this->json(
            $this->llmProfiles->createProfile($request)->toArray(),
            Response::HTTP_CREATED,
        );
    }
}
