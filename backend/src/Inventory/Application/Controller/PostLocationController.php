<?php

declare(strict_types=1);

namespace App\Inventory\Application\Controller;

use App\Inventory\Application\Dto\PostLocationRequest;
use App\Inventory\Application\LocationApplicationService;
use App\SharedKernel\Application\ApiIri;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final class PostLocationController extends AbstractController
{
    #[Route(path: '/api/locations', methods: ['POST'])]
    public function __invoke(#[MapRequestPayload] PostLocationRequest $request, LocationApplicationService $locationApp): JsonResponse
    {
        $name = trim($request->name);
        $parentId = null;
        if (null !== $request->parent && '' !== $request->parent) {
            $parentId = ApiIri::tailAfterPrefix(ApiIri::PREFIX_LOCATION, $request->parent);
        }

        return $this->json($locationApp->createLocation($name, $parentId));
    }
}
