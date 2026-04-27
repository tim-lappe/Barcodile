<?php

declare(strict_types=1);

namespace App\Catalog\Domain\Entity\Embeddable;

use App\SharedKernel\Domain\Barcode;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
final class BarcodeEmbeddable
{
    #[ORM\Column(length: 100, unique: true, nullable: true)]
    private ?string $code = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $type = null;

    public function apply(Barcode $barcode): void
    {
        $this->code = $barcode->getCode();
        $this->type = $barcode->getType();
    }

    public function toValue(): Barcode
    {
        $code = $this->code ?? '';
        $type = $this->type ?? Barcode::DEFAULT_SYMBOLOGY;

        return new Barcode($code, $type);
    }
}
