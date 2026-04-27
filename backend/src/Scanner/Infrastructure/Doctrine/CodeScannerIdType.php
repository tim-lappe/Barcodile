<?php

declare(strict_types=1);

namespace App\Scanner\Infrastructure\Doctrine;

use App\SharedKernel\Domain\Id\CodeScannerId;
use App\SharedKernel\Infrastructure\Doctrine\Type\AbstractUuidIdDoctrineType;

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
