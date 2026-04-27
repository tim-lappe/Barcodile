<?php

declare(strict_types=1);

namespace App\Picnic\Domain\Cart;

use App\SharedKernel\Domain\Id\ShoppingCartLineId;
use DateTimeImmutable;
use Symfony\Component\Uid\Uuid;

final readonly class PicnicCartShoppingCartViewNormalizer
{
    public const string SHOPPING_CART_VIEW_ID = 'picnic';

    private const string LINE_ID_NAMESPACE = '6ba7b810-9dad-11d1-80b4-00c04fd430c8';

    public function __construct(
        private PicnicCartLineRowCollector $lineRowCollector,
    ) {
    }

    /**
     * @param array<mixed> $raw
     */
    public function normalize(array $raw): PicnicCachedCart
    {
        $createdAt = new DateTimeImmutable('now');
        $itemRows = [];
        $seenLineIds = [];
        $this->lineRowCollector->collect($raw, $itemRows, $seenLineIds);

        $lines = [];
        foreach ($itemRows as $i => $row) {
            $lines[] = $this->mapRowToLine($row, $i, $createdAt);
        }

        return new PicnicCachedCart(
            self::SHOPPING_CART_VIEW_ID,
            'Picnic basket',
            $createdAt,
            $lines,
        );
    }

    /**
     * @param array<mixed> $raw
     *
     * @return array{productId: string|null, quantity: int}|null
     */
    public function resolveLineMutationContext(ShoppingCartLineId $lineId, array $raw): ?array
    {
        $itemRows = [];
        $seenLineIds = [];
        $this->lineRowCollector->collect($raw, $itemRows, $seenLineIds);
        foreach ($itemRows as $i => $row) {
            $lineUuid = Uuid::v5(Uuid::fromString(self::LINE_ID_NAMESPACE), 'picnic-cart-line:'.$row['rowKey'].':'.$i);
            if (ShoppingCartLineId::fromUuid($lineUuid)->equals($lineId)) {
                return [
                    'productId' => $row['productId'] ?? null,
                    'quantity' => $row['quantity'],
                ];
            }
        }

        return null;
    }

    /**
     * @param array{rowKey: string, name: string, quantity: int, priceCents?: int, productId?: string} $row
     */
    private function mapRowToLine(array $row, int $index, DateTimeImmutable $createdAt): PicnicCachedCartLine
    {
        $productId = $row['productId'] ?? null;
        $catalogItemId = 'picnic:'.($productId ?? 'line-'.$index);
        $displayName = $row['name'];
        if (isset($row['priceCents'])) {
            $displayName .= ' · €'.number_format($row['priceCents'] / 100.0, 2, '.', '');
        }

        $lineUuid = Uuid::v5(Uuid::fromString(self::LINE_ID_NAMESPACE), 'picnic-cart-line:'.$row['rowKey'].':'.$index);

        return new PicnicCachedCartLine(
            ShoppingCartLineId::fromUuid($lineUuid),
            $row['quantity'],
            $createdAt,
            $catalogItemId,
            $displayName,
        );
    }
}
