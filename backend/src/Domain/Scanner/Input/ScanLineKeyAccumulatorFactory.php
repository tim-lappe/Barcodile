<?php

declare(strict_types=1);

namespace App\Domain\Scanner\Input;

interface ScanLineKeyAccumulatorFactory
{
    public function create(): ScanLineKeyAccumulator;
}
