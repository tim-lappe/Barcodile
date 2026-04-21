<?php

declare(strict_types=1);

namespace App\Infrastructure\Shared\ObjectStorage;

interface ObjectStorageClientInterface
{
    public function ensureBucketExists(StorageBucket $bucket): void;

    public function putObject(PutObjectRequest $request): PutObjectResult;

    public function getObject(GetObjectRequest $request): GetObjectResult;

    public function deleteObject(DeleteObjectRequest $request): void;

    public function headObject(StorageBucket $bucket, ObjectKey $key): ?HeadObjectResult;
}
