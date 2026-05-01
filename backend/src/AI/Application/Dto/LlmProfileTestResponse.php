<?php

declare(strict_types=1);

namespace App\AI\Application\Dto;

use Symfony\Component\Serializer\Attribute\SerializedName;

final readonly class LlmProfileTestResponse
{
    public function __construct(
        #[SerializedName('ok')]
        public bool $success,
        public string $preview,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'ok' => $this->success,
            'preview' => $this->preview,
        ];
    }
}
