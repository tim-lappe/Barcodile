<?php

declare(strict_types=1);

namespace App\Cart\Api\Dto;

use Symfony\Component\Serializer\Attribute\SerializedName;

final readonly class ShoppingCartResponse
{
    /**
     * @param list<ShoppingCartLineResponse> $lines
     */
    public function __construct(
        #[SerializedName('id')]
        public string $cartId,
        public ?string $name,
        public string $createdAt,
        public array $lines,
    ) {
    }
}
