<?php

declare(strict_types=1);

namespace App\Cart\Application\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class PostShoppingCartLineRequest
{
    public function __construct(
        #[Assert\NotBlank]
        public string $shoppingCart,
        #[Assert\NotBlank]
        public string $catalogItem,
        #[Assert\GreaterThanOrEqual(1)]
        public int $quantity,
    ) {
    }
}
