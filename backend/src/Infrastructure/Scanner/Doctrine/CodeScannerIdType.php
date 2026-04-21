<?php

declare(strict_types=1);

namespace App\Infrastructure\Scanner\Doctrine;

use App\Domain\Scanner\Entity\CodeScannerId;
use App\Infrastructure\Shared\Doctrine\Type\AbstractUuidIdDoctrineType;

final class CodeScannerIdType extends AbstractUuidIdDoctrineType
{
    public const NAME = 'code_scanner_id';

    public function getName(): string
    {
        return self::NAME;
    }

    protected function getIdClass(): string
    {
        return CodeScannerId::class;
    }
}
