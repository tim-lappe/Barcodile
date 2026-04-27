<?php

declare(strict_types=1);

namespace App\AI\Infrastructure\Doctrine;

use App\SharedKernel\Domain\Id\LlmProfileId;
use App\SharedKernel\Infrastructure\Doctrine\Type\AbstractUuidIdDoctrineType;

final class LlmProfileIdType extends AbstractUuidIdDoctrineType
{
    public const NAME = 'llm_profile_id';

    public function getName(): string
    {
        return self::NAME;
    }

    protected function getIdClass(): string
    {
        return LlmProfileId::class;
    }
}
