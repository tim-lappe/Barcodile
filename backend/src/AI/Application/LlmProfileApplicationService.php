<?php

declare(strict_types=1);

namespace App\AI\Application;

use App\AI\Application\Dto\LlmProfileResponse;
use App\AI\Application\Dto\LlmProfileTestResponse;
use App\AI\Application\Dto\PostLlmProfileRequest;
use App\AI\Application\Dto\PutLlmProfileRequest;
use App\AI\Domain\Entity\LlmProfile;
use App\AI\Domain\Exception\InvalidLlmProfileConfigurationException;
use App\AI\Domain\Exception\LlmCompletionProbeException;
use App\AI\Domain\LlmProfileKind;
use App\AI\Domain\Port\LlmCompletionProbePort;
use App\AI\Domain\Repository\LlmProfileRepository;
use App\SharedKernel\Domain\Id\LlmProfileId;
use App\SharedKernel\Infrastructure\Security\AppSecretStringCipher;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @SuppressWarnings("PHPMD.ExcessiveClassComplexity")
 */
final readonly class LlmProfileApplicationService
{
    public function __construct(
        private LlmProfileRepository $repository,
        private AppSecretStringCipher $cipher,
        private LlmCompletionProbePort $completionProbe,
    ) {
    }

    /**
     * @return list<LlmProfileResponse>
     */
    public function listProfiles(): array
    {
        $rows = $this->repository->findAllOrderedBySortOrder();
        $out = [];
        foreach ($rows as $row) {
            $out[] = $this->mapResponse($row);
        }

        return $out;
    }

    public function createProfile(PostLlmProfileRequest $request): LlmProfileResponse
    {
        $entity = new LlmProfile();
        $this->applyProfileRequest($entity, $request, $this->repository->nextSortOrder());

        $this->repository->save($entity);

        return $this->mapResponse($entity);
    }

    public function updateProfile(string $profileId, PutLlmProfileRequest $request): LlmProfileResponse
    {
        $entity = $this->mustFind(LlmProfileId::fromString($profileId));
        $this->applyProfileRequest($entity, $request);

        $this->repository->save($entity);

        return $this->mapResponse($entity);
    }

    public function deleteProfile(string $profileId): void
    {
        $entity = $this->mustFind(LlmProfileId::fromString($profileId));
        $this->repository->remove($entity);
    }

    public function testProfile(string $profileId): LlmProfileTestResponse
    {
        $entity = $this->mustFind(LlmProfileId::fromString($profileId));
        $apiKey = $this->apiKeyForTest($entity);

        try {
            $text = $this->completionProbe->probeMinimalCompletion(
                $entity->getKind(),
                $entity->getModel(),
                $apiKey,
                $entity->getBaseUrl(),
            );
        } catch (LlmCompletionProbeException $e) {
            return new LlmProfileTestResponse(false, $e->getMessage());
        }

        $preview = $text;
        if (\strlen($preview) > 500) {
            $preview = substr($preview, 0, 500).'…';
        }

        return new LlmProfileTestResponse(true, $preview);
    }

    private function applyProfileRequest(
        LlmProfile $entity,
        PostLlmProfileRequest|PutLlmProfileRequest $request,
        ?int $defaultSortOrder = null,
    ): void
    {
        $kind = LlmProfileKind::from(trim($request->kind));
        $baseUrl = null === $request->baseUrl ? null : trim($request->baseUrl);
        $apiKey = trim($request->apiKey);
        $apiKeyCipher = $entity->getApiKeyCipher();
        if ('' !== $apiKey) {
            $apiKeyCipher = $this->cipher->encrypt($apiKey, AppSecretStringCipher::HKDF_INFO_LLM_API_KEY);
        }

        try {
            $entity->changeTestableConfiguration(
                $kind,
                LlmProfileKind::OpenAiCompatible === $kind ? $baseUrl : null,
                $apiKeyCipher,
            );
        } catch (InvalidLlmProfileConfigurationException $exception) {
            throw new BadRequestHttpException($exception->getMessage(), $exception);
        }
        $entity->changeLabel(trim($request->label));
        $entity->changeModel(trim($request->model));
        $entity->changeEnabled($request->enabled ?? true);
        $entity->changeSortOrder($request->sortOrder ?? $defaultSortOrder ?? $entity->getSortOrder());
    }

    private function apiKeyForTest(LlmProfile $entity): string
    {
        if (!$entity->hasStoredApiKey()) {
            return '';
        }

        return $this->cipher->decrypt((string) $entity->getApiKeyCipher(), AppSecretStringCipher::HKDF_INFO_LLM_API_KEY);
    }

    private function mustFind(LlmProfileId $profileId): LlmProfile
    {
        $entity = $this->repository->findById($profileId);
        if (!$entity instanceof LlmProfile) {
            throw new NotFoundHttpException('LLM profile not found.');
        }

        return $entity;
    }

    private function mapResponse(LlmProfile $entity): LlmProfileResponse
    {
        return new LlmProfileResponse(
            (string) $entity->getId(),
            $entity->getKind()->value,
            $entity->getLabel(),
            $entity->getModel(),
            $entity->getBaseUrl(),
            $entity->isEnabled(),
            $entity->getSortOrder(),
            $entity->hasStoredApiKey(),
        );
    }

}
