<?php

declare(strict_types=1);

namespace App\Picnic\Infrastructure\Doctrine;

use App\SharedKernel\Domain\Id\PicnicIntegrationSettingsId;
use App\SharedKernel\Infrastructure\Doctrine\Type\AbstractUuidIdDoctrineType;

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
