<?php

declare(strict_types=1);

namespace App\Cart\Domain\Port;

interface CartItemInterface
{
    public function getId(): string;

    public function name(): string;
}
