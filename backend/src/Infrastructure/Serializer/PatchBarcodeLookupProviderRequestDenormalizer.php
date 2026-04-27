<?php

declare(strict_types=1);

namespace App\Infrastructure\Serializer;

use App\Catalog\Application\Dto\PatchBarcodeLookupProviderRequest;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class PatchBarcodeLookupProviderRequestDenormalizer implements DenormalizerInterface
{
    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        DenormalizerArgTrace::noteSupports($format, $context);

        return PatchBarcodeLookupProviderRequest::class === $type && \is_array($data);
    }

    public function getSupportedTypes(?string $format): array
    {
        DenormalizerArgTrace::noteTypes($format);

        return [
            PatchBarcodeLookupProviderRequest::class => true,
        ];
    }

    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): PatchBarcodeLookupProviderRequest
    {
        if (!\is_array($data)) {
            throw new NotNormalizableValueException('Expected an object to patch a barcode lookup provider.');
        }
        DenormalizerArgTrace::noteDenormalize($type, $format, $context);

        return $this->requestFromArray($data);
    }

    /**
     * @param array<mixed> $data
     */
    private function requestFromArray(array $data): PatchBarcodeLookupProviderRequest
    {
        $labelSpecified = \array_key_exists('label', $data);
        $label = $labelSpecified && \is_string($data['label'] ?? null) ? $data['label'] : null;

        $enabledSpecified = \array_key_exists('enabled', $data);
        $enabled = $enabledSpecified ? ($data['enabled'] ?? null) : null;

        $sortOrderSpecified = \array_key_exists('sortOrder', $data);
        $sortOrder = null;
        if ($sortOrderSpecified) {
            $raw = $data['sortOrder'] ?? null;
            if (!\is_int($raw)) {
                throw new BadRequestHttpException('sortOrder must be an integer.');
            }
            $sortOrder = $raw;
        }

        $apiKeySpecified = \array_key_exists('apiKey', $data);
        $apiKey = $apiKeySpecified && \is_string($data['apiKey'] ?? null) ? $data['apiKey'] : null;

        return new PatchBarcodeLookupProviderRequest(
            $labelSpecified,
            $label,
            $enabledSpecified,
            $enabled,
            $sortOrderSpecified,
            $sortOrder,
            $apiKeySpecified,
            $apiKey,
        );
    }
}
