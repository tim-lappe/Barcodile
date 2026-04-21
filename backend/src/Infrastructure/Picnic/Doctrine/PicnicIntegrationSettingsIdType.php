<?php

declare(strict_types=1);

namespace App\Infrastructure\Picnic\Doctrine;

use App\Domain\Picnic\Entity\PicnicIntegrationSettingsId;
use App\Infrastructure\Shared\Doctrine\Type\AbstractUuidIdDoctrineType;

final class PicnicIntegrationSettingsIdType extends AbstractUuidIdDoctrineType
{
    public const NAME = 'picnic_integration_settings_id';

    public function getName(): string
    {
        return self::NAME;
    }

    protected function getIdClass(): string
    {
        return PicnicIntegrationSettingsId::class;
    }
}
