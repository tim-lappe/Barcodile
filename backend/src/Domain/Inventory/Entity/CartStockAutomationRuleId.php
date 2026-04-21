<?php

declare(strict_types=1);

namespace App\Domain\Inventory\Entity;

use App\Domain\Shared\Id\AbstractUuidId;
use Symfony\Component\Uid\Uuid;

final readonly class CartStockAutomationRuleId extends AbstractUuidId
{
    public function __construct(?Uuid $uuid = null)
    {
        parent::__construct($uuid ?? Uuid::v7());
    }

    protected static function newInstance(Uuid $uuid): static
    {
        return new self($uuid);
    }
}
