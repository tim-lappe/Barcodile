<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Doctrine;

use App\SharedKernel\Domain\Id\CartStockAutomationRuleId;
use App\SharedKernel\Infrastructure\Doctrine\Type\AbstractUuidIdDoctrineType;

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
