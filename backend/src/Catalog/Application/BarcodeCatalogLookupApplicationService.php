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
            try {
                return $this->mapResponse($provider->lookup($barcode));
            } catch (BarcodeLookupSkippedException) {
            }
        }

        throw new BadRequestHttpException('No barcode lookup provider produced a result.');
    }

    private function mapResponse(BarcodeCatalogLookupDraft $d): BarcodeCatalogLookupResponse
    {
        $volume = null;
        if (null !== $d->volumeAmount && null !== $d->volumeUnit && '' !== trim($d->volumeAmount)) {
            $volume = new VolumeResponse(trim($d->volumeAmount), $d->volumeUnit);
        }

        $weight = null;
        if (null !== $d->weightAmount && null !== $d->weightUnit && '' !== trim($d->weightAmount)) {
            $weight = new WeightResponse(trim($d->weightAmount), $d->weightUnit);
        }

        return new BarcodeCatalogLookupResponse(
            $d->providerId,
            trim($d->name),
            $volume,
            $weight,
            $d->barcodeCode,
            $d->barcodeType,
            $d->alcoholPercent,
        );
    }
}
