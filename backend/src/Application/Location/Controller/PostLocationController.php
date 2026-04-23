<?php

declare(strict_types=1);

namespace App\Application\Location\Controller;

use App\Application\Location\Dto\PostLocationRequest;
use App\Application\Location\LocationApplicationService;
use App\Application\Shared\ApiIri;
use App\Domain\Shared\Id\LocationId;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final class PostLocationController extends AbstractController
{
    public function __construct(
        private readonly LocationApplicationService $locationApp,
    ) {
    }

    #[Route(path: '/api/locations', methods: ['POST'])]
    public function __invoke(#[MapRequestPayload] PostLocationRequest $request): JsonResponse
    {
        $name = trim($request->name);
        $parentId = null;
        if (null !== $request->parent && '' !== $request->parent) {
            $parentId = LocationId::fromString(ApiIri::tailAfterPrefix(ApiIri::PREFIX_LOCATION, $request->parent));
        }

        return $this->json($this->locationApp->createLocation($name, $parentId));
    }
}
