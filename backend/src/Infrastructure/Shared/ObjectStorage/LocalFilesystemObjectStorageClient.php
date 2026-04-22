<?php

declare(strict_types=1);

namespace App\Infrastructure\Shared\ObjectStorage;

use InvalidArgumentException;
use JsonException;
use Throwable;

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
            if (!is_dir($path) && !@mkdir($path, 0775, true) && !is_dir($path)) {
                throw ObjectStorageException::wrap(
                    'Failed to create bucket.',
                    new \RuntimeException(sprintf('Could not create directory "%s".', $path)),
                );
            }
        } catch (ObjectStorageException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw ObjectStorageException::wrap('Failed to create bucket.', $e);
        }
    }

    public function putObject(PutObjectRequest $request): PutObjectResult
    {
        $blobPath = $this->blobPath($request->bucket, $request->key);
        $dir = dirname($blobPath);

        try {
            if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
                throw ObjectStorageException::wrap(
                    'Failed to put object.',
                    new \RuntimeException(sprintf('Could not create directory "%s".', $dir)),
                );
            }

            if (false === file_put_contents($blobPath, $request->body)) {
                throw ObjectStorageException::wrap(
                    'Failed to put object.',
                    new \RuntimeException(sprintf('Could not write "%s".', $blobPath)),
                );
            }

            $eTag = md5($request->body);
            $metaPayload = json_encode(
                ['contentType' => $request->contentType, 'eTag' => $eTag],
                JSON_THROW_ON_ERROR,
            );
            $metaPath = $this->metaPath($request->bucket, $request->key);
            if (false === file_put_contents($metaPath, $metaPayload)) {
                throw ObjectStorageException::wrap(
                    'Failed to put object.',
                    new \RuntimeException(sprintf('Could not write "%s".', $metaPath)),
                );
            }

            return new PutObjectResult($eTag);
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
            if (!is_file($blobPath)) {
                throw ObjectStorageException::wrap(
                    'Object not found.',
                    new \RuntimeException(sprintf('No file at "%s".', $blobPath)),
                );
            }

            $body = file_get_contents($blobPath);
            if (false === $body) {
                throw ObjectStorageException::wrap(
                    'Failed to get object.',
                    new \RuntimeException(sprintf('Could not read "%s".', $blobPath)),
                );
            }

            $contentLength = strlen($body);
            $contentType = null;
            $eTag = md5($body);
            $metaPath = $this->metaPath($request->bucket, $request->key);
            if (is_file($metaPath)) {
                try {
                    $raw = file_get_contents($metaPath);
                    if (false !== $raw) {
                        $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
                        if (is_array($data)
                            && isset($data['contentType'], $data['eTag'])
                            && is_string($data['contentType'])
                            && is_string($data['eTag'])) {
                            $contentType = $data['contentType'];
                            $eTag = $data['eTag'];
                        }
                    }
                } catch (JsonException) {
                }
            }
            if (null === $contentType && class_exists(\finfo::class)) {
                $finfo = new \finfo(FILEINFO_MIME_TYPE);
                $inferred = $finfo->buffer($body);
                $contentType = false !== $inferred ? $inferred : null;
            }

            return new GetObjectResult($body, $contentType, $contentLength, $eTag);
        } catch (ObjectStorageException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw ObjectStorageException::wrap('Failed to get object.', $e);
        }
    }

    public function deleteObject(DeleteObjectRequest $request): void
    {
        $blobPath = $this->blobPath($request->bucket, $request->key);
        $metaPath = $this->metaPath($request->bucket, $request->key);

        try {
            if (is_file($blobPath)) {
                if (!@unlink($blobPath)) {
                    throw ObjectStorageException::wrap(
                        'Failed to delete object.',
                        new \RuntimeException(sprintf('Could not delete "%s".', $blobPath)),
                    );
                }
            }
            if (is_file($metaPath)) {
                @unlink($metaPath);
            }
        } catch (ObjectStorageException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw ObjectStorageException::wrap('Failed to delete object.', $e);
        }
    }

    public function headObject(StorageBucket $bucket, ObjectKey $key): ?HeadObjectResult
    {
        $blobPath = $this->blobPath($bucket, $key);

        try {
            if (!is_file($blobPath)) {
                return null;
            }

            $length = filesize($blobPath);
            if (false === $length) {
                throw ObjectStorageException::wrap(
                    'Failed to head object.',
                    new \RuntimeException(sprintf('Could not stat "%s".', $blobPath)),
                );
            }

            $metaPath = $this->metaPath($bucket, $key);
            if (is_file($metaPath)) {
                try {
                    $raw = file_get_contents($metaPath);
                    if (false !== $raw) {
                        $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
                        if (is_array($data)
                            && isset($data['contentType'], $data['eTag'])
                            && is_string($data['contentType'])
                            && is_string($data['eTag'])) {
                            return new HeadObjectResult($data['contentType'], $length, $data['eTag']);
                        }
                    }
                } catch (JsonException) {
                }
            }

            $eTag = md5_file($blobPath);
            if (false === $eTag) {
                throw ObjectStorageException::wrap(
                    'Failed to head object.',
                    new \RuntimeException(sprintf('Could not hash "%s".', $blobPath)),
                );
            }

            return new HeadObjectResult(null, $length, $eTag);
        } catch (ObjectStorageException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw ObjectStorageException::wrap('Failed to head object.', $e);
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

