<?php

declare(strict_types=1);

namespace App\Application\Location\Controller;

use App\Application\Location\Dto\PatchLocationRequest;
use App\Application\Location\LocationApplicationService;
use App\Application\Shared\ApiIri;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final class PatchLocationController extends AbstractController
{
    public function __construct(
        private readonly LocationApplicationService $locationApp,
    ) {
    }

    #[Route(path: '/api/locations/{locationId}', methods: ['PATCH'])]
    public function __invoke(string $locationId, #[MapRequestPayload] PatchLocationRequest $request): Response
    {
        $resolvedParent = null;
        if (null !== $request->parent) {
            $resolvedParent = ApiIri::tailAfterPrefix(ApiIri::PREFIX_LOCATION, $request->parent);
        }
        $this->locationApp->updateLocation($locationId, $request->name, $resolvedParent);

        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
