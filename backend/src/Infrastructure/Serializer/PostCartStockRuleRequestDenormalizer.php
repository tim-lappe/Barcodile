<?php

declare(strict_types=1);

namespace App\Infrastructure\Serializer;

use App\Application\Inventory\Dto\PostCartStockAutomationRuleRequest;
use App\Application\Shared\HttpJsonField;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class PostCartStockRuleRequestDenormalizer implements DenormalizerInterface
{
    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        DenormalizerArgTrace::noteSupports($format, $context);

        return PostCartStockAutomationRuleRequest::class === $type && \is_array($data);
    }

    public function getSupportedTypes(?string $format): array
    {
        DenormalizerArgTrace::noteTypes($format);

        return [
            PostCartStockAutomationRuleRequest::class => true,
        ];
    }

    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): PostCartStockAutomationRuleRequest
    {
        if (!\is_array($data)) {
            throw new NotNormalizableValueException('Expected an object for cart stock automation rule.');
        }
        DenormalizerArgTrace::noteDenormalize($type, $format, $context);
        $cart = $data['shoppingCart'] ?? null;
        if (!\is_string($cart)) {
            throw new BadRequestHttpException('Field shoppingCart must be a string.');
        }
        $stockBelow = HttpJsonField::requireInt($data, 'stockBelow');
        $addQuantity = HttpJsonField::requireInt($data, 'addQuantity');
        $enabled = $this->optionalEnabled($data);

        return new PostCartStockAutomationRuleRequest($cart, $stockBelow, $addQuantity, $enabled);
    }

    /**
     * @param array<mixed> $data
     */
    private function optionalEnabled(array $data): bool
    {
        if (!\array_key_exists('enabled', $data)) {
            return true;
        }
        $enabledRaw = $data['enabled'];
        if (!\is_bool($enabledRaw)) {
            throw new BadRequestHttpException('Field enabled must be a boolean.');
        }

        return $enabledRaw;
    }
}
