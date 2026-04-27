<?php

declare(strict_types=1);

namespace App\Catalog\Domain\Entity;

use App\Catalog\Domain\BarcodeLookupProviderKind;
use App\Catalog\Domain\Repository\BarcodeLookupProviderRepository;
use App\SharedKernel\Domain\Id\BarcodeLookupProviderId;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BarcodeLookupProviderRepository::class)]
#[ORM\Table(name: 'barcode_lookup_provider')]
class BarcodeLookupProvider
{
    #[ORM\Id]
    #[ORM\Column(name: 'barcode_lookup_provider_id', type: 'barcode_lookup_provider_id', unique: true)]
    private BarcodeLookupProviderId $barcodeLookupProviderId;

    #[ORM\Column(enumType: BarcodeLookupProviderKind::class, length: 64)]
    private BarcodeLookupProviderKind $kind;

    #[ORM\Column(length: 255)]
    private string $label = '';

    #[ORM\Column(options: ['default' => true])]
    private bool $enabled = true;

    #[ORM\Column(name: 'sort_order', options: ['default' => 0])]
    private int $sortOrder = 0;

    #[ORM\Column(name: 'api_key_cipher', type: 'text', nullable: true)]
    private ?string $apiKeyCipher = null;

    public function __construct()
    {
        $this->barcodeLookupProviderId = new BarcodeLookupProviderId();
        $this->kind = BarcodeLookupProviderKind::BarcodeLookupComV3;
    }

    public function getId(): BarcodeLookupProviderId
    {
        return $this->barcodeLookupProviderId;
    }

    public function getKind(): BarcodeLookupProviderKind
    {
        return $this->kind;
    }

    public function changeKind(BarcodeLookupProviderKind $kind): static
    {
        $this->kind = $kind;

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

    public function getApiKeyCipher(): ?string
    {
        return $this->apiKeyCipher;
    }

    public function changeApiKeyCipher(?string $apiKeyCipher): static
    {
        $this->apiKeyCipher = $apiKeyCipher;

        return $this;
    }

    public function hasStoredApiKey(): bool
    {
        return null !== $this->apiKeyCipher && '' !== $this->apiKeyCipher;
    }
}
