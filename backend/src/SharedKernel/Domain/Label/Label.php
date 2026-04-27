<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Label;

final readonly class Label
{
    public function __construct(
        private LabelContent $content,
        private LabelSize $size,
    ) {
    }

    public function content(): LabelContent
    {
        return $this->content;
    }

    public function size(): LabelSize
    {
        return $this->size;
    }
}
