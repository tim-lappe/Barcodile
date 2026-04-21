<?php

declare(strict_types=1);

namespace App\Infrastructure\Inventory\Doctrine;

use App\Domain\Inventory\Entity\CartStockAutomationRuleId;
use App\Infrastructure\Shared\Doctrine\Type\AbstractUuidIdDoctrineType;

final class CartStockAutomationRuleIdType extends AbstractUuidIdDoctrineType
{
    public const NAME = 'cart_stock_automation_rule_id';

    public function getName(): string
    {
        return self::NAME;
    }

    protected function getIdClass(): string
    {
        return CartStockAutomationRuleId::class;
    }
}
