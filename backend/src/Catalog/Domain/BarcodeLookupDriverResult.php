<?php

declare(strict_types=1);

namespace App\Catalog\Domain;

final readonly class BarcodeLookupDriverResult
{
    private function __construct(
        public bool $ok,
        public ?ResolvedBarcodeProduct $product,
    ) {
    }

    public static function success(ResolvedBarcodeProduct $product): self
    {
        return new self(true, $product);
    }

    public static function notFound(): self
    {
        return new self(false, null);
    }
}
