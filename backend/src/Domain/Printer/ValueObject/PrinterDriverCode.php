<?php

declare(strict_types=1);

namespace App\Domain\Printer\ValueObject;

use InvalidArgumentException;
use Stringable;

final readonly class PrinterDriverCode implements Stringable
{
    private const MAX_LENGTH = 64;

    private string $value;

    public function __construct(string $value)
    {
        $normalized = trim($value);
        if ('' === $normalized) {
            throw new InvalidArgumentException('Printer driver code must not be empty.');
        }
        if (self::MAX_LENGTH < \strlen($normalized)) {
            throw new InvalidArgumentException('Printer driver code exceeds maximum length.');
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
