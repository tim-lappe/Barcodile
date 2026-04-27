<?php

declare(strict_types=1);

namespace App\Infrastructure\Serializer;

use App\Inventory\Application\Dto\PatchLocationRequest;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class PatchLocationRequestDenormalizer implements DenormalizerInterface
{
    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        DenormalizerArgTrace::noteSupports($format, $context);

        return PatchLocationRequest::class === $type && \is_array($data);
    }

    public function getSupportedTypes(?string $format): array
    {
        DenormalizerArgTrace::noteTypes($format);

        return [
            PatchLocationRequest::class => true,
        ];
    }

    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): PatchLocationRequest
    {
        if (!\is_array($data)) {
            throw new NotNormalizableValueException('Expected an object to patch location.');
        }
        DenormalizerArgTrace::noteDenormalize($type, $format, $context);

        return $this->patchFromArray($data);
    }

    /**
     * @param array<mixed> $data
     */
    private function patchFromArray(array $data): PatchLocationRequest
    {
        $name = $this->requireNonEmptyName($data);
        $parentKey = $this->readParentField($data);

        return new PatchLocationRequest(trim($name), \is_string($parentKey) ? $parentKey : null);
    }

    /**
     * @param array<mixed> $data
     */
    private function requireNonEmptyName(array $data): string
    {
        $name = $data['name'] ?? null;
        if (!\is_string($name) || '' === trim($name)) {
            throw new BadRequestHttpException('Field name must be a non-empty string.');
        }

        return $name;
    }

    /**
     * @param array<mixed> $data
     */
    private function readParentField(array $data): mixed
    {
        if (!\array_key_exists('parent', $data)) {
            throw new BadRequestHttpException('Field parent is required.');
        }
        $parentKey = $data['parent'];
        if (null !== $parentKey && !\is_string($parentKey)) {
            throw new BadRequestHttpException('Invalid parent field.');
        }

        return $parentKey;
    }
}
