<?php

declare(strict_types=1);

namespace App\AI\Application;

use App\AI\Application\Dto\LlmProfileResponse;
use App\AI\Application\Dto\LlmProfileTestResponse;
use App\AI\Application\Dto\PatchLlmProfileRequest;
use App\AI\Application\Dto\PostLlmProfileRequest;
use App\AI\Domain\Entity\LlmProfile;
use App\AI\Domain\Exception\LlmCompletionProbeException;
use App\AI\Domain\LlmProfileKind;
use App\AI\Domain\Port\LlmCompletionProbePort;
use App\AI\Domain\Repository\LlmProfileRepository;
use App\SharedKernel\Domain\Id\LlmProfileId;
use App\SharedKernel\Infrastructure\Security\AppSecretStringCipher;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use ValueError;

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
        $label = trim($request->label);
        if ('' === $label) {
            throw new BadRequestHttpException('Field label must be a non-empty string.');
        }
        $model = trim($request->model);
        if ('' === $model) {
            throw new BadRequestHttpException('Field model must be a non-empty string.');
        }
        $kind = $this->parseKind($request->kind);
        $apiKey = trim($request->apiKey);
        $baseUrl = null !== $request->baseUrl ? trim($request->baseUrl) : null;

        if (LlmProfileKind::OpenAi === $kind) {
            if ('' === $apiKey) {
                throw new BadRequestHttpException('Field apiKey is required for openai profiles.');
            }
            $this->assertOpenAiKeyFormat($apiKey);
        }
        if (LlmProfileKind::OpenAiCompatible === $kind) {
            if (null === $baseUrl || '' === $baseUrl) {
                throw new BadRequestHttpException('Field baseUrl is required for openai_compatible profiles.');
            }
        }

        $sortOrder = $request->sortOrder ?? $this->repository->nextSortOrder();
        $enabled = $request->enabled ?? true;

        $entity = new LlmProfile();
        $entity->changeKind($kind);
        $entity->changeLabel($label);
        $entity->changeModel($model);
        $entity->changeBaseUrl(LlmProfileKind::OpenAiCompatible === $kind ? $baseUrl : null);
        $entity->changeEnabled($enabled);
        $entity->changeSortOrder($sortOrder);
        if ('' !== $apiKey) {
            $entity->changeApiKeyCipher($this->cipher->encrypt($apiKey, AppSecretStringCipher::HKDF_INFO_LLM_API_KEY));
        }
        $this->repository->save($entity);

        return $this->mapResponse($entity);
    }

    public function patchProfile(string $id, PatchLlmProfileRequest $request): LlmProfileResponse
    {
        $entity = $this->mustFind(LlmProfileId::fromString($id));
        if ($request->kindSpecified) {
            $entity->changeKind($this->parseKindOrNull($request->kind));
        }
        if ($request->labelSpecified) {
            $label = null === $request->label ? '' : trim($request->label);
            if ('' === $label) {
                throw new BadRequestHttpException('Field label must be a non-empty string.');
            }
            $entity->changeLabel($label);
        }
        if ($request->modelSpecified) {
            $model = null === $request->model ? '' : trim($request->model);
            if ('' === $model) {
                throw new BadRequestHttpException('Field model must be a non-empty string.');
            }
            $entity->changeModel($model);
        }
        if ($request->baseUrlSpecified) {
            $next = null === $request->baseUrl ? null : trim($request->baseUrl);
            $entity->changeBaseUrl('' === $next ? null : $next);
        }
        if ($request->enabledSpecified) {
            if (!\is_bool($request->enabled)) {
                throw new BadRequestHttpException('Field enabled must be a boolean when present.');
            }
            $entity->changeEnabled($request->enabled);
        }
        if ($request->sortOrderSpecified && null !== $request->sortOrder) {
            $entity->changeSortOrder($request->sortOrder);
        }
        if ($request->apiKeySpecified) {
            $next = null === $request->apiKey ? '' : trim($request->apiKey);
            if ('' !== $next) {
                $this->assertOpenAiKeyFormatIfNeeded($entity->getKind(), $next);
                $entity->changeApiKeyCipher($this->cipher->encrypt($next, AppSecretStringCipher::HKDF_INFO_LLM_API_KEY));
            }
        }

        if (LlmProfileKind::OpenAi === $entity->getKind()) {
            $entity->changeBaseUrl(null);
        } else {
            $u = $entity->getBaseUrl();
            if (null === $u || '' === trim($u)) {
                throw new BadRequestHttpException('Field baseUrl is required for openai_compatible profiles.');
            }
        }

        $this->repository->save($entity);

        return $this->mapResponse($entity);
    }

    public function deleteProfile(string $id): void
    {
        $entity = $this->mustFind(LlmProfileId::fromString($id));
        $this->repository->remove($entity);
    }

    public function testProfile(string $id): LlmProfileTestResponse
    {
        $entity = $this->mustFind(LlmProfileId::fromString($id));
        if (!$entity->hasStoredApiKey()) {
            if (LlmProfileKind::OpenAiCompatible !== $entity->getKind()) {
                throw new BadRequestHttpException('No API key is stored for this profile.');
            }
            $apiKey = '';
        } else {
            $apiKey = $this->cipher->decrypt((string) $entity->getApiKeyCipher(), AppSecretStringCipher::HKDF_INFO_LLM_API_KEY);
        }
        if (LlmProfileKind::OpenAi === $entity->getKind() && '' === $apiKey) {
            throw new BadRequestHttpException('No API key is stored for this profile.');
        }
        $this->assertOpenAiKeyFormatIfNeeded($entity->getKind(), $apiKey);
        if (LlmProfileKind::OpenAiCompatible === $entity->getKind()) {
            $u = $entity->getBaseUrl();
            if (null === $u || '' === trim($u)) {
                throw new BadRequestHttpException('baseUrl is missing for this profile.');
            }
        }

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

    private function mustFind(LlmProfileId $id): LlmProfile
    {
        $entity = $this->repository->findById($id);
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

    private function parseKind(string $raw): LlmProfileKind
    {
        $trimmed = trim($raw);
        try {
            return LlmProfileKind::from($trimmed);
        } catch (ValueError) {
            throw new BadRequestHttpException('Invalid kind; expected openai or openai_compatible.');
        }
    }

    private function parseKindOrNull(?string $raw): LlmProfileKind
    {
        if (null === $raw) {
            throw new BadRequestHttpException('Field kind must be a string when present.');
        }

        return $this->parseKind($raw);
    }

    private function assertOpenAiKeyFormat(string $apiKey): void
    {
        if (!str_starts_with($apiKey, 'sk-')) {
            throw new BadRequestHttpException('OpenAI API keys must start with sk-.');
        }
    }

    private function assertOpenAiKeyFormatIfNeeded(LlmProfileKind $kind, string $apiKey): void
    {
        if (LlmProfileKind::OpenAi === $kind && '' !== $apiKey) {
            $this->assertOpenAiKeyFormat($apiKey);
        }
    }
}
