<?php

declare(strict_types=1);

namespace App\Cart\Application;

use App\Cart\Api\Dto\CartProviderIndexEntryResponse;
use App\Cart\Domain\Facade\CartFacade;
use App\Cart\Domain\Facade\CartProviderIndexEntryView;

final readonly class CartProviderIndexApplicationService
{
    public function __construct(
        private CartFacade $carts,
    ) {
    }

    /**
     * @return list<CartProviderIndexEntryResponse>
     */
    public function index(): array
    {
        return array_map(
            static fn (CartProviderIndexEntryView $entry): CartProviderIndexEntryResponse => new CartProviderIndexEntryResponse(
                $entry->providerId,
                $entry->name,
                $entry->lineCount,
                $entry->createdAt,
            ),
            $this->carts->providerIndex(),
        );
    }
}
