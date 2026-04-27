<?php

declare(strict_types=1);

namespace App\AI\Application\Dto;

final readonly class LlmProfileTestResponse
{
    public function __construct(
        public bool $ok,
        public string $preview,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'ok' => $this->ok,
            'preview' => $this->preview,
        ];
    }
}
