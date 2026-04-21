<?php

declare(strict_types=1);

namespace App\Domain\Cart\Port;

interface CartItemInterface
{
    public function getId(): string;

    public function name(): string;
}
