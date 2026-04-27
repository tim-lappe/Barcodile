<?php

declare(strict_types=1);

namespace App\Infrastructure\Serializer;

use App\Inventory\Api\Dto\PatchCartStockAutomationRuleRequest;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class PatchCartStockRuleRequestDenormalizer implements DenormalizerInterface
{
    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        DenormalizerArgTrace::noteSupports($format, $context);

        return PatchCartStockAutomationRuleRequest::class === $type && \is_array($data);
    }

    public function getSupportedTypes(?string $format): array
    {
        DenormalizerArgTrace::noteTypes($format);

        return [
            PatchCartStockAutomationRuleRequest::class => true,
        ];
    }

    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): PatchCartStockAutomationRuleRequest
    {
        if (!\is_array($data)) {
            throw new NotNormalizableValueException('Expected an object to patch automation rule.');
        }
        DenormalizerArgTrace::noteDenormalize($type, $format, $context);

        return $this->patchFromArray($data);
    }

    /**
     * @param array<mixed> $data
     */
    private function patchFromArray(array $data): PatchCartStockAutomationRuleRequest
    {
        [$cartSpecified, $cartIri] = $this->readShoppingCartFields($data);
        $stockBelowSpecified = \array_key_exists('stockBelow', $data);
        $stockBelow = $stockBelowSpecified ? self::optionalInt($data['stockBelow'] ?? null) : null;
        $addQtySpecified = \array_key_exists('addQuantity', $data);
        $addQuantity = $addQtySpecified ? self::optionalInt($data['addQuantity'] ?? null) : null;
        [$enabledSpecified, $enabled] = $this->readEnabledField($data);

        return new PatchCartStockAutomationRuleRequest(
            $cartSpecified,
            $cartIri,
            $stockBelowSpecified,
            $stockBelow,
            $addQtySpecified,
            $addQuantity,
            $enabledSpecified,
            $enabled,
        );
    }

    /**
     * @param array<mixed> $data
     *
     * @return array{0: bool, 1: ?string}
     */
    private function readShoppingCartFields(array $data): array
    {
        $cartSpecified = \array_key_exists('shoppingCart', $data);
        $cartIri = $cartSpecified && \is_string($data['shoppingCart'] ?? null) ? $data['shoppingCart'] : null;

        return [$cartSpecified, $cartIri];
    }

    /**
     * @param array<mixed> $data
     *
     * @return array{0: bool, 1: ?bool}
     */
    private function readEnabledField(array $data): array
    {
        $enabledSpecified = \array_key_exists('enabled', $data);
        if (!$enabledSpecified) {
            return [$enabledSpecified, null];
        }
        $enabledRaw = $data['enabled'];
        if (!\is_bool($enabledRaw)) {
            throw new BadRequestHttpException('enabled must be a boolean.');
        }

        return [$enabledSpecified, $enabledRaw];
    }

    private static function optionalInt(mixed $raw): int
    {
        if (\is_int($raw)) {
            return $raw;
        }
        if (\is_string($raw) && is_numeric($raw)) {
            return (int) $raw;
        }
        throw new BadRequestHttpException('Expected an integer value.');
    }
}
