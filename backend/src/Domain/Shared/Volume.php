<?php

declare(strict_types=1);

namespace App\Domain\Shared;

use InvalidArgumentException;
use Symfony\Component\Serializer\Attribute\Groups;

final readonly class Volume
{
    private string $amount;

    private VolumeUnit $unit;

    public function __construct(string $amount, VolumeUnit $unit)
    {
        if (!is_numeric($amount)) {
            throw new InvalidArgumentException('Volume amount must be numeric.');
        }
        if ((float) $amount < 0) {
            throw new InvalidArgumentException('Volume amount must not be negative.');
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
    public function getUnit(): VolumeUnit
    {
        return $this->unit;
    }
}
