<?php

declare(strict_types=1);

namespace App\Cart\Domain\Port;

use Generator;

interface CartProviderInterface
{
    /**
     * @return Generator<int, CartInterface>
     */
    public function carts(): Generator;
}
