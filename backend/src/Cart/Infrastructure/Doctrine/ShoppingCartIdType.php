<?php

declare(strict_types=1);

namespace App\Cart\Infrastructure\Doctrine;

use App\SharedKernel\Domain\Id\ShoppingCartId;
use App\SharedKernel\Infrastructure\Doctrine\Type\AbstractUuidIdDoctrineType;

final class ShoppingCartIdType extends AbstractUuidIdDoctrineType
{
    public const NAME = ShoppingCartId::DOCTRINE_TYPE_NAME;

    public function getName(): string
    {
        return ShoppingCartId::DOCTRINE_TYPE_NAME;
    }

    protected function getIdClass(): string
    {
        return ShoppingCartId::class;
    }
}
