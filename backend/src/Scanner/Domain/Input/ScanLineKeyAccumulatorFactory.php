<?php

declare(strict_types=1);

namespace App\Scanner\Domain\Input;

interface ScanLineKeyAccumulatorFactory
{
    public function create(): ScanLineKeyAccumulator;
}
