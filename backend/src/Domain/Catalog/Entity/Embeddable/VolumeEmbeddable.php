<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Entity\Embeddable;

use App\Domain\Shared\Volume;
use App\Domain\Shared\VolumeUnit;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
final class VolumeEmbeddable
{
    #[ORM\Column(type: 'decimal', precision: 12, scale: 4, nullable: true)]
    private ?string $amount = null;

    #[ORM\Column(length: 10, nullable: true, enumType: VolumeUnit::class)]
    private ?VolumeUnit $unit = null;

    public function apply(?Volume $volume): void
    {
        if (null === $volume) {
            $this->amount = null;
            $this->unit = null;

            return;
        }

        $this->amount = $volume->getAmount();
        $this->unit = $volume->getUnit();
    }

    public function toValue(): ?Volume
    {
        if (null === $this->amount || null === $this->unit) {
            return null;
        }

        return new Volume($this->amount, $this->unit);
    }
}
