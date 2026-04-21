<?php

declare(strict_types=1);

namespace App\Application\Picnic\Controller;

use App\Application\Picnic\Dto\PostPicnicLoginRequest;
use App\Application\Picnic\PicnicIntegrationApplicationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final class PostPicnicLoginController extends AbstractController
{
    public function __construct(
        private readonly PicnicIntegrationApplicationService $picnicApp,
    ) {
    }

    #[Route(path: '/api/settings/picnic/login', methods: ['POST'])]
    public function __invoke(#[MapRequestPayload] PostPicnicLoginRequest $request): JsonResponse
    {
        return $this->json($this->picnicApp->login($request));
    }
}
