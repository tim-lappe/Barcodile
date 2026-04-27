<?php

declare(strict_types=1);

namespace App\Infrastructure\Serializer;

use App\Inventory\Application\Dto\PatchInventoryItemRequest;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class PatchInventoryItemRequestDenormalizer implements DenormalizerInterface
{
    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        DenormalizerArgTrace::noteSupports($format, $context);

        return PatchInventoryItemRequest::class === $type && \is_array($data);
    }

    public function getSupportedTypes(?string $format): array
    {
        DenormalizerArgTrace::noteTypes($format);

        return [
            PatchInventoryItemRequest::class => true,
        ];
    }

    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): PatchInventoryItemRequest
    {
        if (!\is_array($data)) {
            throw new NotNormalizableValueException('Expected an object to patch inventory item.');
        }
        DenormalizerArgTrace::noteDenormalize($type, $format, $context);

        return $this->patchFromArray($data);
    }

    /**
     * @param array<mixed> $data
     */
    private function patchFromArray(array $data): PatchInventoryItemRequest
    {
        return new PatchInventoryItemRequest(
            $this->requireStringField($data, 'catalogItem', 'Field catalogItem must be a string.'),
            $this->readOptionalLocationIri($data),
            $this->readOptionalExpirationDate($data),
        );
    }

    /**
     * @param array<mixed> $data
     */
    private function requireStringField(array $data, string $key, string $message): string
    {
        $value = $data[$key] ?? null;
        if (!\is_string($value)) {
            throw new BadRequestHttpException($message);
        }

        return $value;
    }

    /**
     * @param array<mixed> $data
     */
    private function readOptionalLocationIri(array $data): ?string
    {
        if (!\array_key_exists('location', $data)) {
            throw new BadRequestHttpException('Field location is required.');
        }

        return $this->optionalStringOrNull($data['location'], 'Invalid location field.');
    }

    /**
     * @param array<mixed> $data
     */
    private function readOptionalExpirationDate(array $data): ?string
    {
        if (!\array_key_exists('expirationDate', $data)) {
            throw new BadRequestHttpException('Field expirationDate is required.');
        }

        return $this->optionalStringOrNull($data['expirationDate'], 'expirationDate must be a string or null.');
    }

    private function optionalStringOrNull(mixed $value, string $invalidMessage): ?string
    {
        if (null === $value) {
            return null;
        }
        if (!\is_string($value)) {
            throw new BadRequestHttpException($invalidMessage);
        }

        return $value;
    }
}
