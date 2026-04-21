<?php

declare(strict_types=1);

namespace App\Infrastructure\Catalog\Storage;

use App\Domain\Catalog\Entity\CatalogItemId;
use App\Infrastructure\Shared\ObjectStorage\DeleteObjectRequest;
use App\Infrastructure\Shared\ObjectStorage\GetObjectRequest;
use App\Infrastructure\Shared\ObjectStorage\GetObjectResult;
use App\Infrastructure\Shared\ObjectStorage\ObjectKey;
use App\Infrastructure\Shared\ObjectStorage\ObjectStorageClientInterface;
use App\Infrastructure\Shared\ObjectStorage\PutObjectRequest;
use App\Infrastructure\Shared\ObjectStorage\PutObjectResult;
use App\Infrastructure\Shared\ObjectStorage\StorageBucket;

final readonly class CatalogItemImageStorage
{
    private const KEY_PREFIX = 'item-types';

    public function __construct(
        private ObjectStorageClientInterface $objectStorage,
        private StorageBucket $bucket,
    ) {
    }

    public function ensureBucketReady(): void
    {
        $this->objectStorage->ensureBucketExists($this->bucket);
    }

    public function objectKeyFor(CatalogItemId $catalogItemId, string $fileName): ObjectKey
    {
        $safe = $this->sanitizeFileName($fileName);

        return new ObjectKey(self::KEY_PREFIX.'/'.$catalogItemId->toUuid()->toRfc4122().'/images/'.$safe);
    }

    public function put(CatalogItemId $catalogItemId, string $fileName, string $binary, ImageContentType $contentType): PutObjectResult
    {
        $key = $this->objectKeyFor($catalogItemId, $fileName);

        return $this->objectStorage->putObject(new PutObjectRequest(
            $this->bucket,
            $key,
            $binary,
            $contentType->value,
        ));
    }

    public function get(CatalogItemId $catalogItemId, string $fileName): GetObjectResult
    {
        $key = $this->objectKeyFor($catalogItemId, $fileName);

        return $this->objectStorage->getObject(new GetObjectRequest($this->bucket, $key));
    }

    public function delete(CatalogItemId $catalogItemId, string $fileName): void
    {
        $key = $this->objectKeyFor($catalogItemId, $fileName);
        $this->objectStorage->deleteObject(new DeleteObjectRequest($this->bucket, $key));
    }

    public function exists(CatalogItemId $catalogItemId, string $fileName): bool
    {
        $key = $this->objectKeyFor($catalogItemId, $fileName);

        return null !== $this->objectStorage->headObject($this->bucket, $key);
    }

    public function sanitizeFileName(string $fileName): string
    {
        $trimmed = trim($fileName);
        $base = basename(str_replace('\\', '/', $trimmed));

        return '' === $base ? 'image' : $base;
    }
}
