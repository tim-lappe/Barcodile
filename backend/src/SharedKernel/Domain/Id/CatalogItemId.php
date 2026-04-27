<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Id;

use Symfony\Component\Uid\Uuid;

final readonly class CatalogItemId extends AbstractUuidId
{
    public const string DOCTRINE_TYPE_NAME = 'catalog_item_id';

    public function __construct(?Uuid $uuid = null)
    {
        parent::__construct($uuid ?? Uuid::v7());
    }

    protected static function newInstance(Uuid $uuid): static
    {
        return new self($uuid);
    }
}
