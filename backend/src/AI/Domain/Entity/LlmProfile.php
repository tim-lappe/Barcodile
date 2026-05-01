<?php

declare(strict_types=1);

namespace App\AI\Domain\Entity;

use App\AI\Domain\Exception\InvalidLlmProfileConfigurationException;
use App\AI\Domain\LlmProfileKind;
use App\AI\Domain\Repository\LlmProfileRepository;
use App\SharedKernel\Domain\Id\LlmProfileId;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LlmProfileRepository::class)]
#[ORM\Table(name: 'llm_profile')]
class LlmProfile
{
    #[ORM\Id]
    #[ORM\Column(name: 'llm_profile_id', type: 'llm_profile_id', unique: true)]
    private LlmProfileId $llmProfileId;

    #[ORM\Column(enumType: LlmProfileKind::class, length: 32)]
    private LlmProfileKind $kind;

    #[ORM\Column(length: 255)]
    private string $label = '';

    #[ORM\Column(length: 255)]
    private string $model = '';

    #[ORM\Column(name: 'base_url', type: 'text', nullable: true)]
    private ?string $baseUrl = null;

    #[ORM\Column(name: 'api_key_cipher', type: 'text', nullable: true)]
    private ?string $apiKeyCipher = null;

    #[ORM\Column(options: ['default' => true])]
    private bool $enabled = true;

    #[ORM\Column(name: 'sort_order', options: ['default' => 0])]
    private int $sortOrder = 0;

    public function __construct()
    {
        $this->llmProfileId = new LlmProfileId();
        $this->kind = LlmProfileKind::OpenAi;
    }

    public function getId(): LlmProfileId
    {
        return $this->llmProfileId;
    }

    public function getKind(): LlmProfileKind
    {
        return $this->kind;
    }

    public function changeTestableConfiguration(
        LlmProfileKind $kind,
        ?string $baseUrl,
        ?string $apiKeyCipher,
    ): static
    {
        $this->assertTestableConfiguration($kind, $baseUrl, $apiKeyCipher);

        $this->kind = $kind;
        $this->baseUrl = LlmProfileKind::OpenAiCompatible === $kind ? $baseUrl : null;
        $this->apiKeyCipher = $apiKeyCipher;

        return $this;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function changeLabel(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function getModel(): string
    {
        return $this->model;
    }

    public function changeModel(string $model): static
    {
        $this->model = $model;

        return $this;
    }

    public function getBaseUrl(): ?string
    {
        return $this->baseUrl;
    }

    public function getApiKeyCipher(): ?string
    {
        return $this->apiKeyCipher;
    }

    public function hasStoredApiKey(): bool
    {
        return null !== $this->apiKeyCipher && '' !== $this->apiKeyCipher;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function changeEnabled(bool $enabled): static
    {
        $this->enabled = $enabled;

        return $this;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function changeSortOrder(int $sortOrder): static
    {
        $this->sortOrder = $sortOrder;

        return $this;
    }

    private function assertTestableConfiguration(
        LlmProfileKind $kind,
        ?string $baseUrl,
        ?string $apiKeyCipher,
    ): void
    {
        if (LlmProfileKind::OpenAi === $kind && (null === $apiKeyCipher || '' === $apiKeyCipher)) {
            throw new InvalidLlmProfileConfigurationException('No API key is stored for this profile.');
        }

        if (LlmProfileKind::OpenAiCompatible === $kind && (null === $baseUrl || '' === trim($baseUrl))) {
            throw new InvalidLlmProfileConfigurationException('baseUrl is missing for this profile.');
        }
    }
}
