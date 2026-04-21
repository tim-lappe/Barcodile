<?php

declare(strict_types=1);

namespace App\Infrastructure\Picnic;

use App\Domain\Picnic\Repository\PicnicIntegrationSettingsRepository;
use App\Infrastructure\Shared\Security\AppSecretStringCipher;
use InvalidArgumentException;

final readonly class PicnicApiConfigFactory
{
    public function __construct(
        private PicnicIntegrationSettingsRepository $picnicSettings,
        private AppSecretStringCipher $cipher,
        private string $defaultCountry,
        private string $defaultApiVersion,
        private string $defaultAuthKey,
        private string $defaultUrl,
    ) {
    }

    public function create(): PicnicApiConfig
    {
        $row = $this->picnicSettings->getSingleton();
        if (!$row->hasStoredPassword() && !$row->hasStoredAuthSession()) {
            return new PicnicApiConfig(
                $this->defaultCountry,
                $this->defaultApiVersion,
                $this->normalizeAuthKey($this->defaultAuthKey),
                $this->normalizeUrl($this->defaultUrl),
            );
        }

        $storedAuthKey = $this->decryptStoredAuthKey($row->getAuthKeyCipher());

        return new PicnicApiConfig(
            $row->getCountryCode(),
            $this->defaultApiVersion,
            $storedAuthKey ?? $this->normalizeAuthKey($this->defaultAuthKey),
            null,
        );
    }

    private function decryptStoredAuthKey(?string $cipherText): ?string
    {
        if (null === $cipherText || '' === $cipherText) {
            return null;
        }
        $plain = $this->tryDecryptAuthKey($cipherText);

        return '' !== $plain ? $plain : null;
    }

    private function tryDecryptAuthKey(string $cipherText): string
    {
        try {
            return $this->cipher->decrypt($cipherText, AppSecretStringCipher::HKDF_INFO_AUTH_KEY);
        } catch (InvalidArgumentException) {
            return '';
        }
    }

    private function normalizeAuthKey(string $authKey): ?string
    {
        return '' !== $authKey ? $authKey : null;
    }

    private function normalizeUrl(string $url): ?string
    {
        return '' !== $url ? $url : null;
    }
}
