<?php

declare(strict_types=1);

namespace App\Infrastructure\Inventory\Doctrine;

use App\Domain\Shared\Id\CartStockAutomationRuleId;
use App\Infrastructure\Shared\Doctrine\Type\AbstractUuidIdDoctrineType;

final class CartStockAutomationRuleIdType extends AbstractUuidIdDoctrineType
{
    public const NAME = CartStockAutomationRuleId::DOCTRINE_TYPE_NAME;

    public function getName(): string
    {
        return CartStockAutomationRuleId::DOCTRINE_TYPE_NAME;
    }

    protected function getIdClass(): string
    {
        return CartStockAutomationRuleId::class;
    }
}
