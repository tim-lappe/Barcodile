<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Label;

interface LabelImageGenerator
{
    public function generate(LabelContent $content, LabelSize $size): string;
}
