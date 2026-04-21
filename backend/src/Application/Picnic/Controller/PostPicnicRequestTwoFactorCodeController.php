<?php

declare(strict_types=1);

namespace App\Application\Picnic\Controller;

use App\Application\Picnic\Dto\PostPicnicRequestTwoFactorCodeRequest;
use App\Application\Picnic\PicnicIntegrationApplicationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final class PostPicnicRequestTwoFactorCodeController extends AbstractController
{
    public function __construct(
        private readonly PicnicIntegrationApplicationService $picnicApp,
    ) {
    }

    #[Route(path: '/api/settings/picnic/login/request_2fa_code', methods: ['POST'])]
    public function __invoke(#[MapRequestPayload] PostPicnicRequestTwoFactorCodeRequest $request): JsonResponse
    {
        return $this->json($this->picnicApp->requestTwoFactorCode($request));
    }
}
