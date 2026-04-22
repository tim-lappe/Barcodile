<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Entity\Embeddable;

use App\Domain\Shared\Barcode;
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
        $type = $this->type ?? 'EAN';

        return new Barcode($code, $type);
    }
}
