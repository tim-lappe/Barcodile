<?php

declare(strict_types=1);

namespace App\Infrastructure\Scanner;

use App\Domain\Scanner\Input\ScanLineKeyAccumulator;
use App\Domain\Scanner\Input\ScanLineKeyAccumulatorFactory;

final class EvdevScanLineKeyAccumulatorFactory implements ScanLineKeyAccumulatorFactory
{
    public function create(): ScanLineKeyAccumulator
    {
        return new EvdevKeyScanLineAccumulator();
    }
}
