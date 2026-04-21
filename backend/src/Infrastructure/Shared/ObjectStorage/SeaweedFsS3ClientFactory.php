<?php

declare(strict_types=1);

namespace App\Infrastructure\Shared\ObjectStorage;

use AsyncAws\S3\S3Client;

final readonly class SeaweedFsS3ClientFactory
{
    public function __construct(
        private string $endpoint,
        private string $region,
        private string $accessKeyId,
        private string $secretAccessKey,
    ) {
    }

    public function create(): S3Client
    {
        return new S3Client([
            'region' => $this->region,
            'endpoint' => $this->endpoint,
            'pathStyleEndpoint' => 'true',
            'accessKeyId' => $this->accessKeyId,
            'accessKeySecret' => $this->secretAccessKey,
        ]);
    }
}
