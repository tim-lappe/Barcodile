<?php

declare(strict_types=1);

namespace App\Domain\Picnic\Cart;

final readonly class PicnicCartLineFieldPicker
{
    public function __construct(
        private PicnicCartLineNumericParser $numericParser,
    ) {
    }

    /**
     * @param array<string, mixed> $cartNode
     */
    public function pickQuantity(array $cartNode): int
    {
        return $this->numericParser->pickQuantity($cartNode);
    }

    /**
     * @param array<string, mixed> $cartNode
     */
    public function pickPriceCents(array $cartNode): ?int
    {
        return $this->numericParser->pickPriceCents($cartNode);
    }

    /**
     * @param array<string, mixed> $cartNode
     */
    public function pickName(array $cartNode): string
    {
        foreach (['name', 'title'] as $key) {
            $trimmed = $this->nonEmptyTrimmedString($cartNode, $key);
            if (null !== $trimmed) {
                return $trimmed;
            }
        }

        return '';
    }

    /**
     * @param array<string, mixed> $cartNode
     */
    public function pickProductId(array $cartNode): ?string
    {
        $fromKeys = $this->nonEmptyStringFromKeys($cartNode, ['product_id', 'productId']);
        if (null !== $fromKeys) {
            return $fromKeys;
        }

        return $this->orderArticleLineProductId($cartNode);
    }

    /**
     * @param array<string, mixed> $cartNode
     * @param list<string>         $keys
     */
    private function nonEmptyStringFromKeys(array $cartNode, array $keys): ?string
    {
        foreach ($keys as $key) {
            $candidate = $this->stringKeyIfNonEmpty($cartNode, $key);
            if (null !== $candidate) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $cartNode
     */
    private function orderArticleLineProductId(array $cartNode): ?string
    {
        if (($cartNode['type'] ?? null) !== 'ORDER_ARTICLE') {
            return null;
        }

        return $this->stringKeyIfNonEmpty($cartNode, 'id');
    }

    /**
     * @param array<string, mixed> $cartNode
     */
    private function nonEmptyTrimmedString(array $cartNode, string $key): ?string
    {
        if (!isset($cartNode[$key]) || !\is_string($cartNode[$key])) {
            return null;
        }
        $trimmedName = trim($cartNode[$key]);

        return '' === $trimmedName ? null : $trimmedName;
    }

    /**
     * @param array<string, mixed> $cartNode
     */
    private function stringKeyIfNonEmpty(array $cartNode, string $key): ?string
    {
        if (!isset($cartNode[$key]) || !\is_string($cartNode[$key])) {
            return null;
        }

        return '' === $cartNode[$key] ? null : $cartNode[$key];
    }
}
