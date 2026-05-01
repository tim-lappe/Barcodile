<?php

declare(strict_types=1);

namespace App\Catalog\Application;

use App\AI\Domain\Exception\OpenAiResponsesWebSearchException;
use App\Catalog\Application\Dto\BarcodeCatalogLookupResponse;
use App\Catalog\Application\Dto\PostBarcodeCatalogLookupRequest;
use App\Catalog\Application\Dto\VolumeResponse;
use App\Catalog\Application\Dto\WeightResponse;
use App\Catalog\Domain\BarcodeLookup\BarcodeCatalogLookupDraft;
use App\Catalog\Domain\Exception\BarcodeLookupSkippedException;
use App\Catalog\Domain\Port\BarcodeCatalogLookupProvider;
use App\SharedKernel\Domain\Barcode;
use InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final readonly class BarcodeCatalogLookupApplicationService
{
    /**
     * @param iterable<BarcodeCatalogLookupProvider> $lookupProviders
     */
    public function __construct(
        private iterable $lookupProviders,
    ) {
    }

    public function lookup(PostBarcodeCatalogLookupRequest $request): BarcodeCatalogLookupResponse
    {
        try {
            return $this->doLookup($request);
        } catch (OpenAiResponsesWebSearchException $e) {
            throw new BadRequestHttpException($e->getMessage(), $e);
        }
    }

    private function doLookup(PostBarcodeCatalogLookupRequest $request): BarcodeCatalogLookupResponse
    {
        try {
            $barcode = new Barcode($request->code, $request->type);
        } catch (InvalidArgumentException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }

        foreach ($this->lookupProviders as $provider) {
            $mapped = $this->tryMapProviderLookup($provider, $barcode);
            if (null !== $mapped) {
                return $mapped;
            }
        }

        throw new BadRequestHttpException('No barcode lookup provider produced a result.');
    }

    private function tryMapProviderLookup(
        BarcodeCatalogLookupProvider $provider,
        Barcode $barcode,
    ): ?BarcodeCatalogLookupResponse {
        try {
            return $this->mapResponse($provider->lookup($barcode));
        } catch (BarcodeLookupSkippedException) {
            return null;
        }
    }

    private function mapResponse(BarcodeCatalogLookupDraft $draft): BarcodeCatalogLookupResponse
    {
        $volume = null;
        if (null !== $draft->volumeAmount && null !== $draft->volumeUnit && '' !== trim($draft->volumeAmount)) {
            $volume = new VolumeResponse(trim($draft->volumeAmount), $draft->volumeUnit);
        }

        $weight = null;
        if (null !== $draft->weightAmount && null !== $draft->weightUnit && '' !== trim($draft->weightAmount)) {
            $weight = new WeightResponse(trim($draft->weightAmount), $draft->weightUnit);
        }

        $picnic = null !== $draft->extras?->picnicProductId ? trim($draft->extras->picnicProductId) : null;
        if ('' === $picnic) {
            $picnic = null;
        }
        $imageUrl = null !== $draft->extras?->productImageUrl ? trim($draft->extras->productImageUrl) : null;
        if ('' === $imageUrl) {
            $imageUrl = null;
        }

        return new BarcodeCatalogLookupResponse(
            $draft->providerId,
            trim($draft->name),
            $volume,
            $weight,
            $draft->barcode->getCode(),
            $draft->barcode->getType(),
            $draft->alcoholPercent,
            $picnic,
            $imageUrl,
        );
    }
}
