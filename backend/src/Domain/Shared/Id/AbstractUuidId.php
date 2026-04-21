<?php

declare(strict_types=1);

namespace App\Domain\Shared\Id;

use Stringable;
use Symfony\Component\Uid\Uuid;

abstract readonly class AbstractUuidId implements Stringable
{
    protected function __construct(private Uuid $value)
    {
    }

    public static function fromString(string $uuid): static
    {
        return static::newInstance(Uuid::fromString($uuid));
    }

    public static function fromUuid(Uuid $uuid): static
    {
        return static::newInstance($uuid);
    }

    abstract protected static function newInstance(Uuid $uuid): static;

    public function toUuid(): Uuid
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return static::class === $other::class && $this->value->equals($other->value);
    }

    public function __toString(): string
    {
        return $this->value->toRfc4122();
    }
}
