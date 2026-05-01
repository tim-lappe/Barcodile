<?php

declare(strict_types=1);

namespace App\Cart\Application\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class PutShoppingCartRequest
{
    public function __construct(
        #[Assert\Length(max: 255)]
        public ?string $name,
    ) {
    }
}
