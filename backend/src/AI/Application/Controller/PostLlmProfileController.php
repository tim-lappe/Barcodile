<?php

declare(strict_types=1);

namespace App\AI\Application\Controller;

use App\AI\Application\Dto\PostLlmProfileRequest;
use App\AI\Application\LlmProfileApplicationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final class PostLlmProfileController extends AbstractController
{
    #[Route(path: '/api/settings/llm-profiles', methods: ['POST'])]
    public function __invoke(#[MapRequestPayload] PostLlmProfileRequest $request, LlmProfileApplicationService $llmProfiles): JsonResponse
    {
        return $this->json(
            $llmProfiles->createProfile($request)->toArray(),
            Response::HTTP_CREATED,
        );
    }
}
