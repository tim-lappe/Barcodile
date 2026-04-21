<?php

declare(strict_types=1);

namespace App\Domain\Picnic\Cart;

final readonly class PicnicCartLineRowCollector
{
    public function __construct(
        private PicnicCartLineFieldPicker $fieldPicker,
    ) {
    }

    /**
     * @param array<int, array{rowKey: string, name: string, quantity: int, priceCents?: int, productId?: string}> $out
     * @param array<string, true>                                                                                  $seenLineIds
     */
    public function collect(mixed $root, array &$out, array &$seenLineIds): void
    {
        $stack = [$root];
        while ([] !== $stack) {
            $node = array_pop($stack);
            $this->dispatchStackNode($node, $stack, $out, $seenLineIds);
        }
    }

    /**
     * @param list<mixed>                                                                                          $stack
     * @param array<int, array{rowKey: string, name: string, quantity: int, priceCents?: int, productId?: string}> $out
     * @param array<string, true>                                                                                  $seenLineIds
     */
    private function dispatchStackNode(mixed $node, array &$stack, array &$out, array &$seenLineIds): void
    {
        if (null === $node) {
            return;
        }
        if (\is_array($node) && $this->isListArray($node)) {
            $this->pushListElementsOnStack($node, $stack);

            return;
        }
        $this->handleNonListArrayNode($node, $stack, $out, $seenLineIds);
    }

    /**
     * @param list<mixed>                                                                                          $stack
     * @param array<int, array{rowKey: string, name: string, quantity: int, priceCents?: int, productId?: string}> $out
     * @param array<string, true>                                                                                  $seenLineIds
     */
    private function handleNonListArrayNode(mixed $node, array &$stack, array &$out, array &$seenLineIds): void
    {
        if (!\is_array($node)) {
            return;
        }
        /** @var array<string, mixed> $cartNode */
        $cartNode = $node;
        if ($this->isCartLineObject($cartNode)) {
            $this->appendCartLineRow($cartNode, $out, $seenLineIds);

            return;
        }
        $this->pushAssocChildrenOnStack($cartNode, $stack);
    }

    /**
     * @param array<mixed> $list
     * @param list<mixed>  $stack
     */
    private function pushListElementsOnStack(array $list, array &$stack): void
    {
        foreach (array_reverse($list, true) as $element) {
            $stack[] = $element;
        }
    }

    /**
     * @param array<string, mixed> $cartNode
     * @param list<mixed>          $stack
     */
    private function pushAssocChildrenOnStack(array $cartNode, array &$stack): void
    {
        $children = [];
        foreach ($cartNode as $childValue) {
            if (\is_array($childValue)) {
                $children[] = $childValue;
            }
        }
        for ($index = \count($children) - 1; $index >= 0; --$index) {
            $stack[] = $children[$index];
        }
    }

    /**
     * @param array<string, mixed>                                                                                 $cartNode
     * @param array<int, array{rowKey: string, name: string, quantity: int, priceCents?: int, productId?: string}> $out
     * @param array<string, true>                                                                                  $seenLineIds
     */
    private function appendCartLineRow(array $cartNode, array &$out, array &$seenLineIds): void
    {
        $rowParts = $this->resolvedRowParts($cartNode);
        if ($this->skipIfDuplicateLineId($rowParts['lineId'], $seenLineIds)) {
            return;
        }
        $out[] = $this->cartLineRowArray(
            $rowParts['name'],
            $rowParts['quantity'],
            $rowParts['priceCents'],
            $rowParts['productId'],
            $rowParts['lineId'],
            $out,
        );
    }

    /**
     * @param array<string, mixed> $cartNode
     *
     * @return array{lineId: ?string, name: string, quantity: int, priceCents: ?int, productId: ?string}
     */
    private function resolvedRowParts(array $cartNode): array
    {
        return [
            'name' => $this->fieldPicker->pickName($cartNode),
            'quantity' => $this->fieldPicker->pickQuantity($cartNode),
            'priceCents' => $this->fieldPicker->pickPriceCents($cartNode),
            'productId' => $this->fieldPicker->pickProductId($cartNode),
            'lineId' => $this->stableLineIdForCartNode($cartNode),
        ];
    }

    /**
     * @param array<int, array{rowKey: string, name: string, quantity: int, priceCents?: int, productId?: string}> $out
     *
     * @return array{rowKey: string, name: string, quantity: int, priceCents?: int, productId?: string}
     */
    private function cartLineRowArray(
        string $name,
        int $quantity,
        ?int $priceCents,
        ?string $productId,
        ?string $lineId,
        array $out,
    ): array {
        $row = [
            'rowKey' => $lineId ?? ('line-'.\count($out)),
            'name' => $name,
            'quantity' => $quantity,
        ];
        if (null !== $priceCents) {
            $row['priceCents'] = $priceCents;
        }
        if (null !== $productId) {
            $row['productId'] = $productId;
        }

        return $row;
    }

    /**
     * @param array<string, mixed> $cartNode
     */
    private function stableLineIdForCartNode(array $cartNode): ?string
    {
        if (($cartNode['type'] ?? null) === 'ORDER_ARTICLE') {
            return null;
        }

        return $this->nonEmptyStringId($cartNode);
    }

    /**
     * @param array<string, mixed> $cartNode
     */
    private function nonEmptyStringId(array $cartNode): ?string
    {
        if (!isset($cartNode['id']) || !\is_string($cartNode['id']) || '' === $cartNode['id']) {
            return null;
        }

        return $cartNode['id'];
    }

    /**
     * @param array<string, true> $seenLineIds
     */
    private function skipIfDuplicateLineId(?string $lineId, array &$seenLineIds): bool
    {
        if (null === $lineId) {
            return false;
        }
        if (isset($seenLineIds[$lineId])) {
            return true;
        }
        $seenLineIds[$lineId] = true;

        return false;
    }

    /**
     * @param array<mixed> $arr
     */
    private function isListArray(array $arr): bool
    {
        if ([] === $arr) {
            return true;
        }

        return array_keys($arr) === range(0, \count($arr) - 1);
    }

    /**
     * @param array<string, mixed> $cartNode
     */
    private function isCartLineObject(array $cartNode): bool
    {
        if ('' === $this->fieldPicker->pickName($cartNode)) {
            return false;
        }
        $pid = $this->fieldPicker->pickProductId($cartNode);
        if (null !== $pid) {
            return true;
        }
        $type = $cartNode['type'] ?? null;

        return \in_array($type, [
            'SINGLE_ARTICLE',
            'CROSS_SELL',
            'RECIPE_ARTICLE',
            'BUNDLE_ARTICLE',
            'PRODUCT',
            'ORDER_ARTICLE',
        ], true);
    }
}
