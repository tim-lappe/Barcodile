<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Label;

use InvalidArgumentException;

final readonly class LabelSize
{
    public function __construct(
        private int $widthMillimeters,
        private int $heightMillimeters,
    ) {
        if ($widthMillimeters <= 0 || $heightMillimeters <= 0) {
            throw new InvalidArgumentException('Label size dimensions must be positive.');
        }
    }

    public function widthMillimeters(): int
    {
        return $this->widthMillimeters;
    }

    public function heightMillimeters(): int
    {
        return $this->heightMillimeters;
    }

    public function area(): int
    {
        return $this->widthMillimeters * $this->heightMillimeters;
    }

    public function fits(self $contentSize): bool
    {
        return $this->widthMillimeters >= $contentSize->widthMillimeters
            && $this->heightMillimeters >= $contentSize->heightMillimeters;
    }

    public function equals(self $other): bool
    {
        return $this->widthMillimeters === $other->widthMillimeters
            && $this->heightMillimeters === $other->heightMillimeters;
    }
}
