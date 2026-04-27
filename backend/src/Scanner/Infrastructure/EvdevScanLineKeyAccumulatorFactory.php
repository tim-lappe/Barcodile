<?php

declare(strict_types=1);

namespace App\Scanner\Infrastructure;

use App\Scanner\Domain\Input\ScanLineKeyAccumulator;
use App\Scanner\Domain\Input\ScanLineKeyAccumulatorFactory;

final class EvdevScanLineKeyAccumulatorFactory implements ScanLineKeyAccumulatorFactory
{
    public function create(): ScanLineKeyAccumulator
    {
        return new EvdevKeyScanLineAccumulator();
    }
}
