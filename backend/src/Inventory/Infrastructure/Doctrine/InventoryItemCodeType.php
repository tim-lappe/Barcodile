<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Doctrine;

use App\Inventory\Domain\ValueObject\InventoryItemCode;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Exception\InvalidType;
use Doctrine\DBAL\Types\Exception\ValueNotConvertible;
use Doctrine\DBAL\Types\Type;
use InvalidArgumentException;

final class InventoryItemCodeType extends Type
{
    public const NAME = 'inventory_item_code';

    private const LENGTH = 32;

    public function getName(): string
    {
        return self::NAME;
    }

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        $column['length'] = self::LENGTH;

        return $platform->getStringTypeDeclarationSQL([
            'length' => $column['length'],
        ]);
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?InventoryItemCode
    {
        $this->requiresSQLCommentHint($platform);
        if ($value instanceof InventoryItemCode || null === $value) {
            return $value;
        }

        return $this->fromString($value);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        $this->requiresSQLCommentHint($platform);
        if ($value instanceof InventoryItemCode) {
            return $value->value();
        }
        if (null === $value || '' === $value) {
            return null;
        }

        return $this->fromString($value)->value();
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        $platform->getStringTypeDeclarationSQL([]);

        return true;
    }

    private function fromString(mixed $value): InventoryItemCode
    {
        if (!\is_string($value)) {
            throw InvalidType::new($value, self::NAME, ['null', 'string', InventoryItemCode::class]);
        }

        try {
            return new InventoryItemCode($value);
        } catch (InvalidArgumentException $exception) {
            throw ValueNotConvertible::new($value, self::NAME, null, $exception);
        }
    }
}
