<?php

declare(strict_types=1);

namespace App\Catalog\Application;

use App\Catalog\Application\Dto\BarcodeLookupProviderResponse;
use App\Catalog\Application\Dto\PatchBarcodeLookupProviderRequest;
use App\Catalog\Application\Dto\PostBarcodeLookupProviderRequest;
use App\Catalog\Domain\BarcodeLookupProviderKind;
use App\Catalog\Domain\Entity\BarcodeLookupProvider;
use App\Catalog\Domain\Repository\BarcodeLookupProviderRepository;
use App\SharedKernel\Domain\Id\BarcodeLookupProviderId;
use App\SharedKernel\Infrastructure\Security\AppSecretStringCipher;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class BarcodeLookupProviderApplicationService
{
    public function __construct(
        private BarcodeLookupProviderRepository $providerRepository,
        private AppSecretStringCipher $cipher,
    ) {
    }

    /**
     * @return list<BarcodeLookupProviderResponse>
     */
    public function listProviders(): array
    {
        $rows = $this->providerRepository->findAllOrderedBySortOrder();
        $out = [];
        foreach ($rows as $row) {
            $out[] = $this->mapResponse($row);
        }

        return $out;
    }

    public function createProvider(PostBarcodeLookupProviderRequest $request): BarcodeLookupProviderResponse
    {
        $label = trim($request->label);
        if ('' === $label) {
            throw new BadRequestHttpException('Field label must be a non-empty string.');
        }
        $apiKey = trim($request->apiKey);
        if ('' === $apiKey) {
            throw new BadRequestHttpException('Field apiKey must be a non-empty string.');
        }

        $kind = $this->parseKindOrDefault($request->kind);
        $sortOrder = $request->sortOrder ?? $this->providerRepository->nextSortOrder();

        $entity = new BarcodeLookupProvider();
        $entity->changeKind($kind);
        $entity->changeLabel($label);
        $entity->changeEnabled($request->enabled);
        $entity->changeSortOrder($sortOrder);
        $entity->changeApiKeyCipher($this->cipher->encrypt($apiKey, AppSecretStringCipher::HKDF_INFO_BARCODE_LOOKUP_API_KEY));
        $this->providerRepository->save($entity);

        return $this->mapResponse($entity);
    }

    public function patchProvider(string $id, PatchBarcodeLookupProviderRequest $request): BarcodeLookupProviderResponse
    {
        $entity = $this->mustFind(BarcodeLookupProviderId::fromString($id));
        if ($request->labelSpecified) {
            $label = null === $request->label ? '' : trim($request->label);
            if ('' === $label) {
                throw new BadRequestHttpException('Field label must be a non-empty string.');
            }
            $entity->changeLabel($label);
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
                $entity->changeApiKeyCipher($this->cipher->encrypt($next, AppSecretStringCipher::HKDF_INFO_BARCODE_LOOKUP_API_KEY));
            }
        }
        $this->providerRepository->save($entity);

        return $this->mapResponse($entity);
    }

    public function deleteProvider(string $id): void
    {
        $entity = $this->mustFind(BarcodeLookupProviderId::fromString($id));
        $this->providerRepository->remove($entity);
    }

    private function mustFind(BarcodeLookupProviderId $id): BarcodeLookupProvider
    {
        $entity = $this->providerRepository->findById($id);
        if (!$entity instanceof BarcodeLookupProvider) {
            throw new NotFoundHttpException('Barcode lookup provider not found.');
        }

        return $entity;
    }

    private function mapResponse(BarcodeLookupProvider $entity): BarcodeLookupProviderResponse
    {
        return new BarcodeLookupProviderResponse(
            (string) $entity->getId(),
            $entity->getKind()->value,
            $entity->getLabel(),
            $entity->isEnabled(),
            $entity->getSortOrder(),
            $entity->hasStoredApiKey(),
        );
    }

    private function parseKindOrDefault(?string $raw): BarcodeLookupProviderKind
    {
        if (null === $raw || '' === trim($raw)) {
            return BarcodeLookupProviderKind::BarcodeLookupComV3;
        }
        $t = trim($raw);
        foreach (BarcodeLookupProviderKind::cases() as $case) {
            if ($case->value === $t) {
                return $case;
            }
        }

        throw new BadRequestHttpException('Unsupported barcode lookup provider kind.');
    }
}
