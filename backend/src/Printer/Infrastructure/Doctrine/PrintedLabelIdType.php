<?php

declare(strict_types=1);

namespace App\Printer\Infrastructure\Doctrine;

use App\SharedKernel\Domain\Id\PrintedLabelId;
use App\SharedKernel\Infrastructure\Doctrine\Type\AbstractUuidIdDoctrineType;

final class PrintedLabelIdType extends AbstractUuidIdDoctrineType
{
    public const NAME = 'printed_label_id';

    public function getName(): string
    {
        return self::NAME;
    }

    protected function getIdClass(): string
    {
        return PrintedLabelId::class;
    }
}
