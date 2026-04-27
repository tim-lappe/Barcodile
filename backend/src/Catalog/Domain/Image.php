<?php

declare(strict_types=1);

namespace App\Catalog\Domain;

final readonly class Image
{
    public function __construct(
        private string $fileName,
        private string $body,
        private string $contentType,
    ) {
    }

    public static function fromCatalogContentType(string $fileName, string $body, CatalogImageContentType $contentType): self
    {
        return new self($fileName, $body, $contentType->value);
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function getContentType(): string
    {
        return $this->contentType;
    }

    public function getMimeType(): string
    {
        return '' === $this->contentType ? 'application/octet-stream' : $this->contentType;
    }

    public function getETag(): string
    {
        return md5($this->body);
    }
}
