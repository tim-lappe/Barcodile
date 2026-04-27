<?php

declare(strict_types=1);

namespace App\Infrastructure\Serializer;

use App\AI\Application\Dto\PatchLlmProfileRequest;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class PatchLlmProfileRequestDenormalizer implements DenormalizerInterface
{
    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        DenormalizerArgTrace::noteSupports($format, $context);

        return PatchLlmProfileRequest::class === $type && \is_array($data);
    }

    public function getSupportedTypes(?string $format): array
    {
        DenormalizerArgTrace::noteTypes($format);

        return [
            PatchLlmProfileRequest::class => true,
        ];
    }

    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): PatchLlmProfileRequest
    {
        if (!\is_array($data)) {
            throw new NotNormalizableValueException('Expected an object to patch an LLM profile.');
        }
        DenormalizerArgTrace::noteDenormalize($type, $format, $context);

        return $this->requestFromArray($data);
    }

    /**
     * @param array<mixed> $data
     */
    private function requestFromArray(array $data): PatchLlmProfileRequest
    {
        $kindSpecified = \array_key_exists('kind', $data);
        $kind = $kindSpecified ? $this->readOptionalString($data, 'kind') : null;

        $labelSpecified = \array_key_exists('label', $data);
        $label = $labelSpecified ? $this->readOptionalString($data, 'label') : null;

        $modelSpecified = \array_key_exists('model', $data);
        $model = $modelSpecified ? $this->readOptionalString($data, 'model') : null;

        $baseUrlSpecified = \array_key_exists('baseUrl', $data);
        $baseUrl = $baseUrlSpecified ? $this->readOptionalString($data, 'baseUrl') : null;

        $enabledSpecified = \array_key_exists('enabled', $data);
        $enabled = $enabledSpecified ? $this->readOptionalBool($data, 'enabled') : null;

        $sortOrderSpecified = \array_key_exists('sortOrder', $data);
        $sortOrder = $sortOrderSpecified ? $this->readOptionalInt($data, 'sortOrder') : null;

        $apiKeySpecified = \array_key_exists('apiKey', $data);
        $apiKey = $apiKeySpecified ? $this->readOptionalString($data, 'apiKey') : null;

        return new PatchLlmProfileRequest(
            $kindSpecified,
            $kind,
            $labelSpecified,
            $label,
            $modelSpecified,
            $model,
            $baseUrlSpecified,
            $baseUrl,
            $enabledSpecified,
            $enabled,
            $sortOrderSpecified,
            $sortOrder,
            $apiKeySpecified,
            $apiKey,
        );
    }

    /**
     * @param array<mixed> $data
     */
    private function readOptionalString(array $data, string $key): ?string
    {
        if (!\array_key_exists($key, $data)) {
            return null;
        }
        $v = $data[$key];
        if (null === $v) {
            return null;
        }
        if (!\is_string($v)) {
            throw new BadRequestHttpException(\sprintf('%s must be a string or null.', $key));
        }

        return $v;
    }

    /**
     * @param array<mixed> $data
     */
    private function readOptionalBool(array $data, string $key): ?bool
    {
        if (!\array_key_exists($key, $data)) {
            return null;
        }
        $v = $data[$key];
        if (null === $v) {
            return null;
        }
        if (!\is_bool($v)) {
            throw new BadRequestHttpException(\sprintf('%s must be a boolean or null.', $key));
        }

        return $v;
    }

    /**
     * @param array<mixed> $data
     */
    private function readOptionalInt(array $data, string $key): ?int
    {
        if (!\array_key_exists($key, $data)) {
            return null;
        }
        $v = $data[$key];
        if (null === $v) {
            return null;
        }
        if (!\is_int($v)) {
            throw new BadRequestHttpException(\sprintf('%s must be an integer or null.', $key));
        }

        return $v;
    }
}
