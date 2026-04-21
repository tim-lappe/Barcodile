<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Model;

final readonly class CatalogItemImageUploadInput
{
    public function __construct(
        public string $temporaryPathname,
        public int $byteSize,
        public ?string $mimeType,
        public string $originalFileName,
    ) {
    }
}
