<?php

declare(strict_types=1);

namespace App\Inventory\Domain\ValueObject;

use InvalidArgumentException;
use Stringable;

final readonly class InventoryItemCode implements Stringable
{
    private const MAX_LENGTH = 32;

    private string $value;

    public function __construct(string $value)
    {
        $normalized = trim($value);
        if ('' === $normalized) {
            throw new InvalidArgumentException('Inventory item code must not be empty.');
        }
        if (1 !== preg_match('/^\d+$/', $normalized)) {
            throw new InvalidArgumentException('Inventory item code must contain digits only.');
        }
        if (self::MAX_LENGTH < \strlen($normalized)) {
            throw new InvalidArgumentException('Inventory item code exceeds maximum length.');
        }
        $this->value = $normalized;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
