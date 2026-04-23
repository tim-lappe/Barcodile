<?php

declare(strict_types=1);

namespace App\Infrastructure\Catalog\Storage;

use App\Domain\Catalog\CatalogImageContentType;
use App\Domain\Catalog\CatalogItemImageBlob;
use App\Domain\Catalog\Exception\CatalogItemImageNotFoundInStorage;
use App\Domain\Catalog\Port\CatalogItemImageStoragePort;
use App\Domain\Shared\Id\CatalogItemId;
use App\Infrastructure\Shared\ObjectStorage\DeleteObjectRequest;
use App\Infrastructure\Shared\ObjectStorage\GetObjectRequest;
use App\Infrastructure\Shared\ObjectStorage\ObjectKey;
use App\Infrastructure\Shared\ObjectStorage\ObjectStorageClientInterface;
use App\Infrastructure\Shared\ObjectStorage\ObjectStorageException;
use App\Infrastructure\Shared\ObjectStorage\PutObjectRequest;
use App\Infrastructure\Shared\ObjectStorage\StorageBucket;

final readonly class CatalogItemImageStorage implements CatalogItemImageStoragePort
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

    public function put(CatalogItemId $catalogItemId, string $fileName, string $binary, CatalogImageContentType $contentType): void
    {
        $key = $this->objectKeyFor($catalogItemId, $fileName);
        $this->objectStorage->putObject(new PutObjectRequest(
            $this->bucket,
            $key,
            $binary,
            $contentType->value,
        ));
    }

    public function get(CatalogItemId $catalogItemId, string $fileName): CatalogItemImageBlob
    {
        $key = $this->objectKeyFor($catalogItemId, $fileName);
        try {
            $got = $this->objectStorage->getObject(new GetObjectRequest($this->bucket, $key));
        } catch (ObjectStorageException $e) {
            if (str_contains($e->getMessage(), 'Object not found')) {
                throw new CatalogItemImageNotFoundInStorage('Image not found in storage.', 0, $e);
            }
            throw $e;
        }

        return new CatalogItemImageBlob($got->body, $got->contentType, $got->eTag);
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
