<?php

declare(strict_types=1);

namespace App\Infrastructure\Serializer;

use App\Cart\Application\Dto\PutShoppingCartRequest;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class PutShoppingCartRequestDenormalizer implements DenormalizerInterface
{
    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        DenormalizerArgTrace::noteSupports($format, $context);

        return PutShoppingCartRequest::class === $type && \is_array($data);
    }

    public function getSupportedTypes(?string $format): array
    {
        DenormalizerArgTrace::noteTypes($format);

        return [
            PutShoppingCartRequest::class => true,
        ];
    }

    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): PutShoppingCartRequest
    {
        if (!\is_array($data)) {
            throw new NotNormalizableValueException('Expected an object to update shopping cart.');
        }
        DenormalizerArgTrace::noteDenormalize($type, $format, $context);

        return $this->requestFromArray($data);
    }

    /**
     * @param array<mixed> $data
     */
    private function requestFromArray(array $data): PutShoppingCartRequest
    {
        if (!\array_key_exists('name', $data)) {
            throw new BadRequestHttpException('Field name is required.');
        }
        $nameValue = $data['name'];
        if (null === $nameValue) {
            return new PutShoppingCartRequest(null);
        }
        if (!\is_string($nameValue)) {
            throw new BadRequestHttpException('Field name must be a string or null.');
        }

        return new PutShoppingCartRequest($nameValue);
    }
}
