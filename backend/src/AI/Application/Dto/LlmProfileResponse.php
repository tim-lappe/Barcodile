<?php

declare(strict_types=1);

namespace App\AI\Application\Dto;

use Symfony\Component\Serializer\Attribute\SerializedName;

final readonly class LlmProfileResponse
{
    public function __construct(
        #[SerializedName('id')]
        public string $profileId,
        public string $kind,
        public string $label,
        public string $model,
        public ?string $baseUrl,
        public bool $enabled,
        public int $sortOrder,
        public bool $hasStoredApiKey,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->profileId,
            'kind' => $this->kind,
            'label' => $this->label,
            'model' => $this->model,
            'baseUrl' => $this->baseUrl,
            'enabled' => $this->enabled,
            'sortOrder' => $this->sortOrder,
            'hasStoredApiKey' => $this->hasStoredApiKey,
        ];
    }
}
