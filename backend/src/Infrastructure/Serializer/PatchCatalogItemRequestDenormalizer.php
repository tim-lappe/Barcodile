<?php

declare(strict_types=1);

namespace App\Infrastructure\Serializer;

use App\Catalog\Application\Dto\CatalogBarcodeInput;
use App\SharedKernel\Domain\Barcode;
use App\Catalog\Application\Dto\CatalogItemAttributeRowInput;
use App\Catalog\Application\Dto\CatalogVolumeInput;
use App\Catalog\Application\Dto\CatalogWeightInput;
use App\Catalog\Application\Dto\PatchCatalogItemRelationsPatch;
use App\Catalog\Application\Dto\PatchCatalogItemRequest;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class PatchCatalogItemRequestDenormalizer implements DenormalizerInterface
{
    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        DenormalizerArgTrace::noteSupports($format, $context);

        return PatchCatalogItemRequest::class === $type && \is_array($data);
    }

    public function getSupportedTypes(?string $format): array
    {
        DenormalizerArgTrace::noteTypes($format);

        return [
            PatchCatalogItemRequest::class => true,
        ];
    }

    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): PatchCatalogItemRequest
    {
        if (!\is_array($data)) {
            throw new NotNormalizableValueException('Expected an object for PATCH catalog item.');
        }
        DenormalizerArgTrace::noteDenormalize($type, $format, $context);

        return $this->catalogPatchFromArray($data);
    }

    /**
     * @param array<mixed> $data
     */
    private function catalogPatchFromArray(array $data): PatchCatalogItemRequest
    {
        [$nameSpecified, $name] = $this->parseNameField($data);
        $volumeSpecified = \array_key_exists('volume', $data);
        $weightSpecified = \array_key_exists('weight', $data);
        $barcodeSpecified = \array_key_exists('barcode', $data);

        return new PatchCatalogItemRequest(
            $nameSpecified,
            $name,
            $volumeSpecified,
            $this->optionalVolumeWhen($volumeSpecified, $data['volume'] ?? null),
            $weightSpecified,
            $this->optionalWeightWhen($weightSpecified, $data['weight'] ?? null),
            $barcodeSpecified,
            $this->optionalBarcodeWhen($barcodeSpecified, $data['barcode'] ?? null),
            $this->relationsPatchFromArray($data),
        );
    }

    private function optionalVolumeWhen(bool $specified, mixed $raw): ?CatalogVolumeInput
    {
        return $specified ? $this->optionalVolume($raw) : null;
    }

    private function optionalWeightWhen(bool $specified, mixed $raw): ?CatalogWeightInput
    {
        return $specified ? $this->optionalWeight($raw) : null;
    }

    private function optionalBarcodeWhen(bool $specified, mixed $raw): ?CatalogBarcodeInput
    {
        return $specified ? $this->optionalBarcode($raw) : null;
    }

    /**
     * @param array<mixed> $data
     */
    private function relationsPatchFromArray(array $data): PatchCatalogItemRelationsPatch
    {
        $attrsSpecified = \array_key_exists('catalogItemAttributes', $data);
        $picnicSpecified = \array_key_exists('linkedPicnicProductId', $data);

        return new PatchCatalogItemRelationsPatch(
            $attrsSpecified,
            $this->optionalAttributeRowsWhen($attrsSpecified, $data['catalogItemAttributes'] ?? null),
            $picnicSpecified,
            $this->optionalLinkedPicnicWhen($picnicSpecified, $data['linkedPicnicProductId'] ?? null),
        );
    }

    /**
     * @return list<CatalogItemAttributeRowInput>|null
     */
    private function optionalAttributeRowsWhen(bool $specified, mixed $raw): ?array
    {
        return $specified ? $this->optionalAttributeRows($raw) : null;
    }

    private function optionalLinkedPicnicWhen(bool $specified, mixed $raw): ?string
    {
        return $specified ? $this->optionalLinkedPicnicProductId($raw) : null;
    }

    /**
     * @param array<mixed> $data
     *
     * @return array{0: bool, 1: ?string}
     */
    private function parseNameField(array $data): array
    {
        $nameSpecified = \array_key_exists('name', $data);
        if (!$nameSpecified) {
            return [false, null];
        }
        $nameRaw = $data['name'];
        if (!\is_string($nameRaw) || '' === trim($nameRaw)) {
            throw new BadRequestHttpException('Field name must be a non-empty string.');
        }

        return [true, trim($nameRaw)];
    }

    private function optionalVolume(mixed $raw): ?CatalogVolumeInput
    {
        if (null === $raw) {
            return null;
        }
        if (!\is_array($raw)) {
            throw new BadRequestHttpException('Invalid volume payload.');
        }
        [$amountRaw, $unit] = $this->requireAmountUnitStrings($raw, 'Volume amount and unit must be strings.');

        return new CatalogVolumeInput($amountRaw, $unit);
    }

    private function optionalWeight(mixed $raw): ?CatalogWeightInput
    {
        if (null === $raw) {
            return null;
        }
        if (!\is_array($raw)) {
            throw new BadRequestHttpException('Invalid weight payload.');
        }
        [$amountRaw, $unit] = $this->requireAmountUnitStrings($raw, 'Weight amount and unit must be strings.');

        return new CatalogWeightInput($amountRaw, $unit);
    }

    /**
     * @param array<mixed> $raw
     *
     * @return array{0: string, 1: string}
     */
    private function requireAmountUnitStrings(array $raw, string $errorMessage): array
    {
        $amountRaw = $raw['amount'] ?? null;
        $unit = $raw['unit'] ?? null;
        if (!\is_string($amountRaw) || !\is_string($unit)) {
            throw new BadRequestHttpException($errorMessage);
        }

        return [$amountRaw, $unit];
    }

    private function optionalBarcode(mixed $raw): ?CatalogBarcodeInput
    {
        if (null === $raw) {
            return null;
        }
        if (!\is_array($raw)) {
            throw new BadRequestHttpException('Invalid barcode payload.');
        }

        return self::barcodeInputFromArray($raw);
    }

    /**
     * @param array<mixed> $raw
     */
    private static function barcodeInputFromArray(array $raw): CatalogBarcodeInput
    {
        $code = $raw['code'] ?? null;
        if (!\is_string($code)) {
            throw new BadRequestHttpException('Barcode code must be a string.');
        }
        if ('' === trim($code)) {
            throw new BadRequestHttpException('Barcode code must not be empty when a barcode object is supplied.');
        }
        $typeRaw = $raw['type'] ?? Barcode::DEFAULT_SYMBOLOGY;

        return new CatalogBarcodeInput($code, \is_string($typeRaw) ? $typeRaw : Barcode::DEFAULT_SYMBOLOGY);
    }

    /**
     * @return list<CatalogItemAttributeRowInput>|null
     */
    private function optionalAttributeRows(mixed $raw): ?array
    {
        if (null === $raw || !\is_array($raw)) {
            return null;
        }

        return $this->mapAttributeRowsList($raw);
    }

    /**
     * @param array<mixed> $raw
     *
     * @return list<CatalogItemAttributeRowInput>
     */
    private function mapAttributeRowsList(array $raw): array
    {
        $out = [];
        foreach ($raw as $row) {
            if (!\is_array($row)) {
                continue;
            }
            $out[] = $this->attributeRowFromArray($row);
        }

        return $out;
    }

    /**
     * @param array<mixed> $row
     */
    private function attributeRowFromArray(array $row): CatalogItemAttributeRowInput
    {
        $attr = $row['attribute'] ?? null;
        if (!\is_string($attr)) {
            throw new BadRequestHttpException('Catalog attribute key must be a string.');
        }
        $rowId = \array_key_exists('id', $row) && \is_string($row['id']) ? $row['id'] : null;

        return new CatalogItemAttributeRowInput($rowId, $attr, $row['value'] ?? null);
    }

    private function optionalLinkedPicnicProductId(mixed $raw): ?string
    {
        if (null === $raw) {
            return null;
        }
        if (!\is_string($raw)) {
            throw new BadRequestHttpException('linkedPicnicProductId must be a string.');
        }

        return $raw;
    }
}
