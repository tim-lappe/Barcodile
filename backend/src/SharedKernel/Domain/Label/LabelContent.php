<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Label;

use InvalidArgumentException;

final readonly class LabelContent
{
    private const TYPE_QR_CODE = 'qr_code';
    private const TYPE_TEXT = 'text';

    private function __construct(
        private string $type,
        private string $value,
    ) {
        if ('' === trim($value)) {
            throw new InvalidArgumentException('Label content value must not be empty.');
        }
    }

    public static function qrCode(string $value): self
    {
        return new self(self::TYPE_QR_CODE, $value);
    }

    public static function text(string $value): self
    {
        return new self(self::TYPE_TEXT, $value);
    }

    public function type(): string
    {
        return $this->type;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function isQrCode(): bool
    {
        return self::TYPE_QR_CODE === $this->type;
    }

    public function isText(): bool
    {
        return self::TYPE_TEXT === $this->type;
    }
}
