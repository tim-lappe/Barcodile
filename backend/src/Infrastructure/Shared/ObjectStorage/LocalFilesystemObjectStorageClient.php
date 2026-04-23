<?php

declare(strict_types=1);

namespace App\Infrastructure\Shared\ObjectStorage;

use finfo;
use InvalidArgumentException;
use JsonException;
use RuntimeException;
use Throwable;

/**
 * @SuppressWarnings("PHPMD.ExcessiveClassComplexity")
 */
final readonly class LocalFilesystemObjectStorageClient implements ObjectStorageClientInterface
{
    public function __construct(
        private string $rootDirectory,
    ) {
        if ('' === $this->rootDirectory) {
            throw new InvalidArgumentException('Root directory must not be empty.');
        }
    }

    public function ensureBucketExists(StorageBucket $bucket): void
    {
        $path = $this->bucketPath($bucket);

        try {
            $this->createDirectoryIfMissing($path);
        } catch (ObjectStorageException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw ObjectStorageException::wrap('Failed to create bucket.', $e);
        }
    }

    public function putObject(PutObjectRequest $request): PutObjectResult
    {
        $blobPath = $this->blobPath($request->bucket, $request->key);

        try {
            $this->createDirectoryIfMissing(\dirname($blobPath));
            $this->writeObjectBlob($request, $blobPath);

            return new PutObjectResult(md5($request->body));
        } catch (ObjectStorageException $e) {
            throw $e;
        } catch (JsonException $e) {
            throw ObjectStorageException::wrap('Failed to put object.', $e);
        } catch (Throwable $e) {
            throw ObjectStorageException::wrap('Failed to put object.', $e);
        }
    }

    public function getObject(GetObjectRequest $request): GetObjectResult
    {
        $blobPath = $this->blobPath($request->bucket, $request->key);

        try {
            return $this->getObjectForExistingBlob($request, $blobPath);
        } catch (ObjectStorageException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw ObjectStorageException::wrap('Failed to get object.', $e);
        }
    }

    /**
     * @SuppressWarnings("PHPMD.CyclomaticComplexity")
     */
    private function getObjectForExistingBlob(GetObjectRequest $request, string $blobPath): GetObjectResult
    {
        if (!is_file($blobPath)) {
            throw ObjectStorageException::wrap('Object not found.', new RuntimeException(\sprintf('No file at "%s".', $blobPath)));
        }
        $body = $this->readStringFromFile($blobPath);
        $contentLength = \strlen($body);
        $eTag = md5($body);
        $contentType = null;
        $fromMeta = $this->readObjectMetaPair($this->metaPath($request->bucket, $request->key));
        if (null !== $fromMeta) {
            $contentType = $fromMeta['contentType'];
            $eTag = $fromMeta['eTag'];
        }
        if (null === $contentType && class_exists(finfo::class)) {
            $fileInfo = new finfo(\FILEINFO_MIME_TYPE);
            $inferred = $fileInfo->buffer($body);
            $contentType = false !== $inferred ? $inferred : null;
        }

        return new GetObjectResult($body, $contentType, $contentLength, $eTag);
    }

    /**
     * @SuppressWarnings("PHPMD.CyclomaticComplexity")
     */
    public function deleteObject(DeleteObjectRequest $request): void
    {
        $blobPath = $this->blobPath($request->bucket, $request->key);
        $metaPath = $this->metaPath($request->bucket, $request->key);

        try {
            if (is_file($blobPath) && !unlink($blobPath)) {
                throw ObjectStorageException::wrap('Failed to delete object.', new RuntimeException(\sprintf('Could not delete "%s".', $blobPath)));
            }
            $this->unlinkIfPresent($metaPath);
        } catch (ObjectStorageException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw ObjectStorageException::wrap('Failed to delete object.', $e);
        }
    }

    /**
     * @SuppressWarnings("PHPMD.CyclomaticComplexity")
     */
    public function headObject(StorageBucket $bucket, ObjectKey $key): ?HeadObjectResult
    {
        $blobPath = $this->blobPath($bucket, $key);

        try {
            if (!is_file($blobPath)) {
                return null;
            }
            $length = $this->readFileSize($blobPath);
            $fromMeta = $this->readObjectMetaPair($this->metaPath($bucket, $key));
            if (null !== $fromMeta) {
                return new HeadObjectResult($fromMeta['contentType'], $length, $fromMeta['eTag']);
            }
            $eTag = $this->readFileMd5($blobPath);

            return new HeadObjectResult(null, $length, $eTag);
        } catch (ObjectStorageException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw ObjectStorageException::wrap('Failed to head object.', $e);
        }
    }

    private function createDirectoryIfMissing(string $path): void
    {
        if (is_dir($path)) {
            return;
        }
        if (!mkdir($path, 0775, true) && !is_dir($path)) {
            throw ObjectStorageException::wrap('Failed to create bucket.', new RuntimeException(\sprintf('Could not create directory "%s".', $path)));
        }
    }

    private function writeObjectBlob(PutObjectRequest $request, string $blobPath): void
    {
        if (false === file_put_contents($blobPath, $request->body)) {
            throw ObjectStorageException::wrap('Failed to put object.', new RuntimeException(\sprintf('Could not write "%s".', $blobPath)));
        }
        $eTag = md5($request->body);
        $metaPayload = json_encode(
            ['contentType' => $request->contentType, 'eTag' => $eTag],
            \JSON_THROW_ON_ERROR,
        );
        $metaPath = $this->metaPath($request->bucket, $request->key);
        if (false === file_put_contents($metaPath, $metaPayload)) {
            throw ObjectStorageException::wrap('Failed to put object.', new RuntimeException(\sprintf('Could not write "%s".', $metaPath)));
        }
    }

    private function readStringFromFile(string $path): string
    {
        $body = file_get_contents($path);
        if (false === $body) {
            throw ObjectStorageException::wrap('Failed to get object.', new RuntimeException(\sprintf('Could not read "%s".', $path)));
        }

        return $body;
    }

    /**
     * @return array{contentType: string, eTag: string}|null
     *
     * @SuppressWarnings("PHPMD.CyclomaticComplexity")
     */
    private function readObjectMetaPair(string $metaPath): ?array
    {
        if (!is_file($metaPath)) {
            return null;
        }
        $raw = file_get_contents($metaPath);
        if (false === $raw) {
            return null;
        }
        $data = json_decode($raw, true);
        if (\JSON_ERROR_NONE !== json_last_error() || !\is_array($data) || !isset($data['contentType'], $data['eTag'])
            || !\is_string($data['contentType']) || !\is_string($data['eTag'])) {
            return null;
        }

        return ['contentType' => $data['contentType'], 'eTag' => $data['eTag']];
    }

    private function readFileSize(string $blobPath): int
    {
        $length = filesize($blobPath);
        if (false === $length) {
            throw ObjectStorageException::wrap('Failed to head object.', new RuntimeException(\sprintf('Could not stat "%s".', $blobPath)));
        }

        return $length;
    }

    private function readFileMd5(string $blobPath): string
    {
        $eTag = md5_file($blobPath);
        if (false === $eTag) {
            throw ObjectStorageException::wrap('Failed to head object.', new RuntimeException(\sprintf('Could not hash "%s".', $blobPath)));
        }

        return $eTag;
    }

    private function unlinkIfPresent(string $path): void
    {
        if (!is_file($path)) {
            return;
        }
        set_error_handler(static function (): true {
            return true;
        });
        try {
            unlink($path);
        } finally {
            restore_error_handler();
        }
    }

    private function bucketPath(StorageBucket $bucket): string
    {
        return $this->rootDirectory.'/'.$bucket->name;
    }

    private function blobPath(StorageBucket $bucket, ObjectKey $key): string
    {
        return $this->bucketPath($bucket).'/'.$key->value;
    }

    private function metaPath(StorageBucket $bucket, ObjectKey $key): string
    {
        return $this->blobPath($bucket, $key).'.meta.json';
    }
}
