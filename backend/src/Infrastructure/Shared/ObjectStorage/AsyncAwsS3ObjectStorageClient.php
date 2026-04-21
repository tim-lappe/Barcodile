<?php

declare(strict_types=1);

namespace App\Infrastructure\Shared\ObjectStorage;

use AsyncAws\S3\Exception\BucketAlreadyExistsException;
use AsyncAws\S3\Exception\BucketAlreadyOwnedByYouException;
use AsyncAws\S3\Exception\NoSuchKeyException;
use AsyncAws\S3\S3Client;
use Throwable;

final readonly class AsyncAwsS3ObjectStorageClient implements ObjectStorageClientInterface
{
    public function __construct(
        private S3Client $s3Client,
    ) {
    }

    public function ensureBucketExists(StorageBucket $bucket): void
    {
        try {
            $this->s3Client->createBucket(['Bucket' => $bucket->name])->resolve();
        } catch (BucketAlreadyExistsException|BucketAlreadyOwnedByYouException $ignored) {
            if (!str_contains($ignored->getMessage(), $ignored->getMessage())) {
                throw $ignored;
            }
        } catch (Throwable $e) {
            throw ObjectStorageException::wrap('Failed to create bucket.', $e);
        }
    }

    public function putObject(PutObjectRequest $request): PutObjectResult
    {
        try {
            $output = $this->s3Client->putObject([
                'Bucket' => $request->bucket->name,
                'Key' => $request->key->value,
                'Body' => $request->body,
                'ContentType' => $request->contentType,
            ]);
            $output->resolve();
            $eTag = $output->getETag();

            return new PutObjectResult(null !== $eTag ? trim($eTag, '"') : null);
        } catch (Throwable $e) {
            throw ObjectStorageException::wrap('Failed to put object.', $e);
        }
    }

    public function getObject(GetObjectRequest $request): GetObjectResult
    {
        try {
            $output = $this->s3Client->getObject([
                'Bucket' => $request->bucket->name,
                'Key' => $request->key->value,
            ]);
            $output->resolve();
            $eTag = $output->getETag();
            $body = $output->getBody()->getContentAsString();

            return new GetObjectResult(
                $body,
                $output->getContentType(),
                $output->getContentLength(),
                null !== $eTag ? trim($eTag, '"') : null,
            );
        } catch (NoSuchKeyException $e) {
            throw ObjectStorageException::wrap('Object not found.', $e);
        } catch (Throwable $e) {
            throw ObjectStorageException::wrap('Failed to get object.', $e);
        }
    }

    public function deleteObject(DeleteObjectRequest $request): void
    {
        try {
            $this->s3Client->deleteObject([
                'Bucket' => $request->bucket->name,
                'Key' => $request->key->value,
            ])->resolve();
        } catch (Throwable $e) {
            throw ObjectStorageException::wrap('Failed to delete object.', $e);
        }
    }

    public function headObject(StorageBucket $bucket, ObjectKey $key): ?HeadObjectResult
    {
        try {
            $output = $this->s3Client->headObject([
                'Bucket' => $bucket->name,
                'Key' => $key->value,
            ]);
            $output->resolve();
            $eTag = $output->getETag();

            return new HeadObjectResult(
                $output->getContentType(),
                $output->getContentLength(),
                null !== $eTag ? trim($eTag, '"') : null,
            );
        } catch (NoSuchKeyException) {
            return null;
        } catch (Throwable $e) {
            throw ObjectStorageException::wrap('Failed to head object.', $e);
        }
    }
}
