<?php

declare(strict_types=1);

namespace App\Application\Debug\Controller;

use App\Application\Debug\LogApplicationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class GetLogsController extends AbstractController
{
    public function __construct(
        private readonly LogApplicationService $logsService,
    ) {
    }

    #[Route(path: '/api/debug/logs', methods: ['GET'])]
    public function __invoke(Request $request): JsonResponse
    {
        return $this->json($this->logsService->listRecentLogs(
            limit: $request->query->getInt('limit', 200),
            level: $this->queryString($request, 'level'),
            query: $this->queryString($request, 'query'),
            channel: $this->queryString($request, 'channel'),
        ));
    }

    private function queryString(Request $request, string $key): ?string
    {
        $value = $request->query->get($key);

        return \is_string($value) ? $value : null;
    }
}
