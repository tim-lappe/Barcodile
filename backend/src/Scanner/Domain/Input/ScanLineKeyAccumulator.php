<?php

declare(strict_types=1);

namespace App\Scanner\Domain\Input;

interface ScanLineKeyAccumulator
{
    public function process(int $type, int $code, int $value): ?string;
}
