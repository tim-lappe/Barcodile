<?php

declare(strict_types=1);

namespace App\Picnic\Domain\Cart;

final readonly class PicnicCartLineNumericParser
{
    /**
     * @param array<string, mixed> $cartNode
     */
    public function pickQuantity(array $cartNode): int
    {
        $fromInt = $this->firstPositiveIntFromKeys($cartNode, ['count', 'quantity', 'unit_quantity', 'unitQuantity']);
        if (null !== $fromInt) {
            return $fromInt;
        }

        return 1;
    }

    /**
     * @param array<string, mixed> $cartNode
     */
    public function pickPriceCents(array $cartNode): ?int
    {
        return $this->firstIntOrFloatAsIntFromKeys(
            $cartNode,
            ['display_price', 'displayPrice', 'line_price', 'linePrice', 'total_price', 'totalPrice'],
        );
    }

    /**
     * @param array<string, mixed> $cartNode
     * @param list<string>         $keys
     */
    private function firstIntOrFloatAsIntFromKeys(array $cartNode, array $keys): ?int
    {
        foreach ($keys as $key) {
            if (!isset($cartNode[$key])) {
                continue;
            }
            $asInt = $this->intOrFloatAsInt($cartNode[$key]);
            if (null !== $asInt) {
                return $asInt;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $cartNode
     * @param list<string>         $keys
     */
    private function firstPositiveIntFromKeys(array $cartNode, array $keys): ?int
    {
        foreach ($keys as $key) {
            $positive = $this->positiveIntAtKey($cartNode, $key);
            if (null !== $positive) {
                return $positive;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $cartNode
     */
    private function positiveIntAtKey(array $cartNode, string $key): ?int
    {
        if (!isset($cartNode[$key])) {
            return null;
        }

        return $this->positiveIntFromValue($cartNode[$key]);
    }

    private function positiveIntFromValue(mixed $value): ?int
    {
        return match (true) {
            \is_int($value) && $value > 0 => $value,
            \is_float($value) && $value > 0.0 => (int) $value,
            default => null,
        };
    }

    private function intOrFloatAsInt(mixed $value): ?int
    {
        if (\is_int($value)) {
            return $value;
        }
        if (\is_float($value)) {
            return (int) $value;
        }

        return null;
    }
}
