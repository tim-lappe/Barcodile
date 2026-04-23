<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Port;

use App\Domain\Catalog\CatalogImageContentType;
use App\Domain\Catalog\CatalogItemImageBlob;
use App\Domain\Catalog\Exception\CatalogItemImageNotFoundInStorage;
use App\Domain\Shared\Id\CatalogItemId;

interface CatalogItemImageStoragePort
{
    public function ensureBucketReady(): void;

    public function sanitizeFileName(string $fileName): string;

    public function put(CatalogItemId $catalogItemId, string $fileName, string $binary, CatalogImageContentType $contentType): void;

    /**
     * @throws CatalogItemImageNotFoundInStorage
     */
    public function get(CatalogItemId $catalogItemId, string $fileName): CatalogItemImageBlob;

    public function delete(CatalogItemId $catalogItemId, string $fileName): void;
}
