<?php

declare(strict_types=1);

namespace App\Domain\Shared\Id;

use Symfony\Component\Uid\Uuid;

final readonly class PicnicIntegrationSettingsId extends AbstractUuidId
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
