<?php

declare(strict_types=1);

namespace App\Cart\Application\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class PutShoppingCartLineRequest
{
    public function __construct(
        #[Assert\GreaterThanOrEqual(1)]
        public int $quantity,
    ) {
    }
}
