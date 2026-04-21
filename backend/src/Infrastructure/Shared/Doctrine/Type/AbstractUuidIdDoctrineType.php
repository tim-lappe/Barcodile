<?php

declare(strict_types=1);

namespace App\Infrastructure\Shared\Doctrine\Type;

use App\Domain\Shared\Id\AbstractUuidId;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Exception\InvalidType;
use Doctrine\DBAL\Types\Exception\ValueNotConvertible;
use Doctrine\DBAL\Types\Type;
use InvalidArgumentException;
use Symfony\Component\Uid\Uuid;
use Throwable;

abstract class AbstractUuidIdDoctrineType extends Type
{
    abstract public function getName(): string;

    /**
     * @return class-string<AbstractUuidId>
     */
    abstract protected function getIdClass(): string;

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        if ($this->hasNativeGuidType($platform)) {
            return $platform->getGuidTypeDeclarationSQL($column);
        }

        return $platform->getBinaryTypeDeclarationSQL([
            'length' => 16,
            'fixed' => true,
        ]);
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?AbstractUuidId
    {
        $this->requiresSQLCommentHint($platform);
        if ($value instanceof AbstractUuidId || null === $value) {
            return $value;
        }

        if (!\is_string($value)) {
            $this->throwInvalidType($value);
        }

        return $this->uuidIdFromUuidString($value);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if ($value instanceof AbstractUuidId) {
            return $this->uuidIdToDatabaseString($value, $platform);
        }

        if (null === $value || '' === $value) {
            return null;
        }

        return $this->databaseStringFromScalar($value, $platform);
    }

    private function databaseStringFromScalar(mixed $value, AbstractPlatform $platform): string
    {
        if (!\is_string($value)) {
            $this->throwInvalidType($value);
        }

        return $this->uuidIdToDatabaseString($this->uuidIdFromUuidString($value), $platform);
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        $platform->getGuidTypeDeclarationSQL([]);

        return true;
    }

    private function uuidIdFromUuidString(string $value): AbstractUuidId
    {
        $class = $this->getIdClass();

        try {
            return $class::fromUuid(Uuid::fromString($value));
        } catch (InvalidArgumentException $exception) {
            $this->throwValueNotConvertible($value, $exception);
        }
    }

    private function uuidIdToDatabaseString(AbstractUuidId $uuidId, AbstractPlatform $platform): string
    {
        return $this->hasNativeGuidType($platform)
            ? $uuidId->toUuid()->toRfc4122()
            : $uuidId->toUuid()->toBinary();
    }

    private function hasNativeGuidType(AbstractPlatform $platform): bool
    {
        return $platform->getGuidTypeDeclarationSQL([]) !== $platform->getStringTypeDeclarationSQL(['fixed' => true, 'length' => 36]);
    }

    private function throwInvalidType(mixed $value): never
    {
        throw InvalidType::new($value, $this->getName(), ['null', 'string', AbstractUuidId::class]);
    }

    private function throwValueNotConvertible(mixed $value, Throwable $previous): never
    {
        throw ValueNotConvertible::new($value, $this->getName(), null, $previous);
    }
}
