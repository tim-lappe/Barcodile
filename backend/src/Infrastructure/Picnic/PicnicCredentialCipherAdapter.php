<?php

declare(strict_types=1);

namespace App\Infrastructure\Picnic;

use App\Domain\Picnic\Port\PicnicCredentialCipherPort;
use App\Infrastructure\Shared\Security\AppSecretStringCipher;

final readonly class PicnicCredentialCipherAdapter implements PicnicCredentialCipherPort
{
    public function __construct(private AppSecretStringCipher $cipher)
    {
    }

    public function encryptAuthKeyForStorage(string $plainText): string
    {
        return $this->cipher->encrypt($plainText, AppSecretStringCipher::HKDF_INFO_AUTH_KEY);
    }

    public function encryptPasswordForStorage(string $plainText): string
    {
        return $this->cipher->encrypt($plainText, AppSecretStringCipher::HKDF_INFO_PASSWORD);
    }
}
