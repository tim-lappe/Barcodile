<?php

declare(strict_types=1);

namespace App\Domain\Shared;

use InvalidArgumentException;
use Symfony\Component\Serializer\Attribute\Groups;

final readonly class Barcode
{
    private string $code;

    private string $type;

    public function __construct(string $code, string $type)
    {
        $trimmedCode = trim($code);
        $this->assertValidCode($trimmedCode);
        $normalizedType = $this->normalizedType($type);
        $this->assertValidTypeLength($normalizedType);

        $this->code = $trimmedCode;
        $this->type = $normalizedType;
    }

    #[Groups(['barcode:read', 'barcode:write', 'catalog_item:read'])]
    public function getCode(): string
    {
        return $this->code;
    }

    #[Groups(['barcode:read', 'barcode:write', 'catalog_item:read'])]
    public function getType(): string
    {
        return $this->type;
    }

    private function assertValidCode(string $trimmedCode): void
    {
        if ('' === $trimmedCode) {
            throw new InvalidArgumentException('Barcode code must not be empty.');
        }
        if (\strlen($trimmedCode) > 100) {
            throw new InvalidArgumentException('Barcode code exceeds maximum length.');
        }
    }

    private function normalizedType(string $type): string
    {
        return '' !== trim($type) ? trim($type) : 'EAN';
    }

    private function assertValidTypeLength(string $normalizedType): void
    {
        if (\strlen($normalizedType) > 50) {
            throw new InvalidArgumentException('Barcode type exceeds maximum length.');
        }
    }
}
