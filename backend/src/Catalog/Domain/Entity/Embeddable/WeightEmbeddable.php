<?php

declare(strict_types=1);

namespace App\Catalog\Domain\Entity\Embeddable;

use App\SharedKernel\Domain\Weight;
use App\SharedKernel\Domain\WeightUnit;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
final class WeightEmbeddable
{
    #[ORM\Column(type: 'decimal', precision: 12, scale: 4, nullable: true)]
    private ?string $amount = null;

    #[ORM\Column(length: 10, nullable: true, enumType: WeightUnit::class)]
    private ?WeightUnit $unit = null;

    public function apply(?Weight $weight): void
    {
        if (null === $weight) {
            $this->amount = null;
            $this->unit = null;

            return;
        }

        $this->amount = $weight->getAmount();
        $this->unit = $weight->getUnit();
    }

    public function toValue(): ?Weight
    {
        if (null === $this->amount || null === $this->unit) {
            return null;
        }

        return new Weight($this->amount, $this->unit);
    }
}
