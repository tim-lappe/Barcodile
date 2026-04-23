<?php

declare(strict_types=1);

namespace App\Domain\Scanner\Input;

interface ScanLineKeyAccumulator
{
    public function process(int $type, int $code, int $value): ?string;
}
