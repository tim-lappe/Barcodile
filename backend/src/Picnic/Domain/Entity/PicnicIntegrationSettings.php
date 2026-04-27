<?php

declare(strict_types=1);

namespace App\Picnic\Domain\Entity;

use App\Picnic\Domain\Repository\PicnicIntegrationSettingsRepository;
use App\SharedKernel\Domain\Id\PicnicIntegrationSettingsId;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: PicnicIntegrationSettingsRepository::class)]
class PicnicIntegrationSettings
{
    #[ORM\Id]
    #[ORM\Column(type: 'picnic_integration_settings_id', unique: true)]
    private PicnicIntegrationSettingsId $settingsId;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $username = null;

    #[ORM\Column(length: 2)]
    private string $countryCode = 'NL';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $passwordCipher = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $authKeyCipher = null;

    #[SerializedName('password')]
    private ?string $plainPassword = null;

    private ?bool $authSessionClear = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $cartDisplayName = null;

    public function __construct()
    {
        $this->settingsId = new PicnicIntegrationSettingsId();
    }

    public function getId(): PicnicIntegrationSettingsId
    {
        return $this->settingsId;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function changeUsername(?string $username): static
    {
        $this->username = $username;

        return $this;
    }

    public function getCountryCode(): string
    {
        return $this->countryCode;
    }

    public function changeCountryCode(string $countryCode): static
    {
        $this->countryCode = $countryCode;

        return $this;
    }

    public function getPasswordCipher(): ?string
    {
        return $this->passwordCipher;
    }

    public function changePasswordCipher(?string $passwordCipher): static
    {
        $this->passwordCipher = $passwordCipher;

        return $this;
    }

    public function getAuthKeyCipher(): ?string
    {
        return $this->authKeyCipher;
    }

    public function changeAuthKeyCipher(?string $authKeyCipher): static
    {
        $this->authKeyCipher = $authKeyCipher;

        return $this;
    }

    public function changePlainPassword(?string $plainPassword): static
    {
        $this->plainPassword = $plainPassword;

        return $this;
    }

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function clearPlainPassword(): static
    {
        $this->plainPassword = null;

        return $this;
    }

    public function getAuthSessionClear(): ?bool
    {
        return $this->authSessionClear;
    }

    public function changeAuthSessionClear(?bool $authSessionClear): static
    {
        $this->authSessionClear = $authSessionClear;

        return $this;
    }

    public function getCartDisplayName(): ?string
    {
        return $this->cartDisplayName;
    }

    public function changeCartDisplayName(?string $cartDisplayName): static
    {
        $this->cartDisplayName = $cartDisplayName;

        return $this;
    }

    #[SerializedName('hasStoredPassword')]
    public function hasStoredPassword(): bool
    {
        return null !== $this->passwordCipher && '' !== $this->passwordCipher;
    }

    #[SerializedName('hasStoredAuthSession')]
    public function hasStoredAuthSession(): bool
    {
        return null !== $this->authKeyCipher && '' !== $this->authKeyCipher;
    }

    public function validateWhenCredentialsStored(ExecutionContextInterface $context): void
    {
        if (!$this->hasStoredPassword() && !$this->hasStoredAuthSession()) {
            return;
        }
        $this->assertPicnicUsername($context);
        $this->assertPicnicSecretOrCredential($context);
    }

    private function assertPicnicUsername(ExecutionContextInterface $context): void
    {
        $username = null !== $this->username ? trim($this->username) : '';
        if ('' === $username) {
            $context->buildViolation('A username is required when Picnic credentials are stored.')
                ->atPath('username')
                ->addViolation();
        }
    }

    private function assertPicnicSecretOrCredential(ExecutionContextInterface $context): void
    {
        if ($this->hasAnyStoredCredential()) {
            return;
        }
        $context->buildViolation('Log in with Picnic or store a password when using stored credentials.')
            ->atPath('password')
            ->addViolation();
    }

    private function hasAnyStoredCredential(): bool
    {
        return $this->hasPasswordCipher() || $this->hasPlainPasswordProvided() || $this->hasAuthKeyCipher();
    }

    private function hasPasswordCipher(): bool
    {
        return null !== $this->passwordCipher && '' !== $this->passwordCipher;
    }

    private function hasPlainPasswordProvided(): bool
    {
        $plain = $this->plainPassword;

        return null !== $plain && '' !== $plain;
    }

    private function hasAuthKeyCipher(): bool
    {
        return null !== $this->authKeyCipher && '' !== $this->authKeyCipher;
    }
}
