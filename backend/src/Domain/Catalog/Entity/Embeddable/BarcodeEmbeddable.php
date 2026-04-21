<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Entity\Embeddable;

use App\Domain\Shared\Barcode;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
final class BarcodeEmbeddable
{
    #[ORM\Column(length: 100, unique: true)]
    private string $code = '';

    #[ORM\Column(length: 50)]
    private string $type = 'EAN';

    public function apply(Barcode $barcode): void
    {
        $this->code = $barcode->getCode();
        $this->type = $barcode->getType();
    }

    public function toValue(): Barcode
    {
        return new Barcode($this->code, $this->type);
    }
}
