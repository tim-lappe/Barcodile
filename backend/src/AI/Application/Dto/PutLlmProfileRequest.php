<?php

declare(strict_types=1);

namespace App\AI\Application\Dto;

use App\AI\Domain\LlmProfileKind;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[Assert\Callback('validate')]
final readonly class PutLlmProfileRequest
{
    public function __construct(
        #[Assert\NotBlank(normalizer: 'trim')]
        #[Assert\Choice(choices: [LlmProfileKind::OpenAi->value, LlmProfileKind::OpenAiCompatible->value])]
        public string $kind,
        #[Assert\NotBlank(normalizer: 'trim')]
        public string $label,
        #[Assert\NotBlank(normalizer: 'trim')]
        public string $model,
        public string $apiKey,
        public ?string $baseUrl = null,
        public ?bool $enabled = null,
        public ?int $sortOrder = null,
    ) {
    }

    public function validate(ExecutionContextInterface $context): void
    {
        $this->validateOpenAiApiKey($context);
        $this->validateOpenAiCompatibleBaseUrl($context);
    }

    private function validateOpenAiApiKey(ExecutionContextInterface $context): void
    {
        $apiKey = trim($this->apiKey);
        if ('' === $apiKey || LlmProfileKind::OpenAi->value !== trim($this->kind)) {
            return;
        }

        if (!str_starts_with($apiKey, 'sk-')) {
            $context->buildViolation('OpenAI API keys must start with sk-.')
                ->atPath('apiKey')
                ->addViolation();
        }
    }

    private function validateOpenAiCompatibleBaseUrl(ExecutionContextInterface $context): void
    {
        if (LlmProfileKind::OpenAiCompatible->value !== trim($this->kind)) {
            return;
        }

        if ($this->hasBaseUrl()) {
            return;
        }

        $context->buildViolation('Field baseUrl is required for openai_compatible profiles.')
            ->atPath('baseUrl')
            ->addViolation();
    }

    private function hasBaseUrl(): bool
    {
        if (null === $this->baseUrl) {
            return false;
        }

        return '' !== trim($this->baseUrl);
    }
}
