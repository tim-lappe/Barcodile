<?php

declare(strict_types=1);

namespace App\Domain\Cart\Port;

use Generator;

interface CartProviderInterface
{
    /**
     * @return Generator<int, CartInterface>
     */
    public function carts(): Generator;
}
