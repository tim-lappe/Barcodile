<?php

declare(strict_types=1);

namespace App\Application\Cart;

use App\Application\Cart\Dto\CartProviderIndexEntryResponse;
use App\Domain\Cart\Port\CartProviderRegistry;
use DateTimeInterface;

final readonly class CartProviderIndexApplicationService
{
    public function __construct(
        private CartProviderRegistry $cartProviderRegistry,
    ) {
    }

    /**
     * @return list<CartProviderIndexEntryResponse>
     */
    public function index(): array
    {
        $out = [];
        foreach ($this->cartProviderRegistry->indexEntries() as $entry) {
            $out[] = new CartProviderIndexEntryResponse(
                $entry->providerId,
                $entry->name,
                $entry->lineCount,
                $entry->createdAt->format(DateTimeInterface::ATOM),
            );
        }

        return $out;
    }
}
