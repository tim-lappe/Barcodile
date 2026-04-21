<?php

declare(strict_types=1);

namespace App\Domain\Shared;

use InvalidArgumentException;
use Symfony\Component\Serializer\Attribute\Groups;

final readonly class Weight
{
    private string $amount;

    private WeightUnit $unit;

    public function __construct(string $amount, WeightUnit $unit)
    {
        if (!is_numeric($amount)) {
            throw new InvalidArgumentException('Weight amount must be numeric.');
        }
        if ((float) $amount < 0) {
            throw new InvalidArgumentException('Weight amount must not be negative.');
        }

        $this->amount = $amount;
        $this->unit = $unit;
    }

    #[Groups(['catalog_item:read', 'catalog_item:write', 'inventory_item:read'])]
    public function getAmount(): string
    {
        return $this->amount;
    }

    #[Groups(['catalog_item:read', 'catalog_item:write', 'inventory_item:read'])]
    public function getUnit(): WeightUnit
    {
        return $this->unit;
    }
}
