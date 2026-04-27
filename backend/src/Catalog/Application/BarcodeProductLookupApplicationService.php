<?php

declare(strict_types=1);

namespace App\Catalog\Application;

use App\Catalog\Application\Dto\BarcodeCatalogProductHintResponse;
use App\Catalog\Domain\BarcodeLookupProviderKind;
use App\Catalog\Domain\Entity\BarcodeLookupProvider;
use App\Catalog\Domain\Port\BarcodeLookupDriver;
use App\Catalog\Domain\Repository\BarcodeLookupProviderRepository;
use App\Catalog\Domain\ResolvedBarcodeProduct;
use App\SharedKernel\Infrastructure\Security\AppSecretStringCipher;
use InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

final readonly class BarcodeProductLookupApplicationService
{
    /**
     * @param iterable<BarcodeLookupDriver> $drivers
     */
    public function __construct(
        private BarcodeLookupProviderRepository $providerRepository,
        private AppSecretStringCipher $cipher,
        private iterable $drivers,
    ) {
    }

    public function hintByBarcode(string $rawBarcode): BarcodeCatalogProductHintResponse
    {
        $barcode = trim($rawBarcode);
        if ('' === $barcode) {
            throw new BadRequestHttpException('Query parameter barcode must be a non-empty string.');
        }

        $providers = $this->providerRepository->findEnabledWithApiKeyOrderedBySortOrder();
        if ([] === $providers) {
            throw new ServiceUnavailableHttpException(
                null,
                'No enabled barcode lookup provider with an API key is configured. Add one under Settings, Barcode lookup.',
            );
        }

        foreach ($providers as $provider) {
            $resolved = $this->lookupWithProvider($provider, $barcode);
            if (null !== $resolved) {
                return $this->mapHint($provider, $resolved);
            }
        }

        throw new NotFoundHttpException('No product was found for this barcode using your configured providers.');
    }

    public function resolvePrimaryNameForBarcode(string $rawBarcode): string
    {
        $hint = $this->hintByBarcode($rawBarcode);
        $name = trim($hint->name);
        if ('' === $name) {
            throw new BadRequestHttpException('Field name must be a non-empty string.');
        }

        return $name;
    }

    private function lookupWithProvider(BarcodeLookupProvider $provider, string $barcode): ?ResolvedBarcodeProduct
    {
        $cipherText = $provider->getApiKeyCipher();
        if (null === $cipherText || '' === $cipherText) {
            return null;
        }

        try {
            $apiKey = $this->cipher->decrypt($cipherText, AppSecretStringCipher::HKDF_INFO_BARCODE_LOOKUP_API_KEY);
        } catch (InvalidArgumentException) {
            return null;
        }

        $driver = $this->driverForKind($provider->getKind());
        if (null === $driver) {
            return null;
        }

        $result = $driver->lookup($apiKey, $barcode);
        if (!$result->ok || null === $result->product) {
            return null;
        }

        return $result->product;
    }

    private function driverForKind(BarcodeLookupProviderKind $kind): ?BarcodeLookupDriver
    {
        foreach ($this->drivers as $driver) {
            if ($driver->supports($kind)) {
                return $driver;
            }
        }

        return null;
    }

    private function mapHint(BarcodeLookupProvider $provider, ResolvedBarcodeProduct $product): BarcodeCatalogProductHintResponse
    {
        return new BarcodeCatalogProductHintResponse(
            (string) $provider->getId(),
            $provider->getLabel(),
            $product->name,
            $product->brand,
            $product->imageUrl,
            $product->category,
            $product->barcodeCode,
            $product->barcodeType,
        );
    }
}
