<?php

declare(strict_types=1);

namespace App\SharedKernel\Infrastructure\Security;

use InvalidArgumentException;
use RuntimeException;

final readonly class AppSecretStringCipher
{
    public const HKDF_INFO_PASSWORD = 'barcodile.picnic.password';

    public const HKDF_INFO_AUTH_KEY = 'barcodile.picnic.auth_key';

    public const HKDF_INFO_PENDING_LOGIN = 'barcodile.picnic.pending_login';

    public const HKDF_INFO_LLM_API_KEY = 'barcodile.llm.api_key';

    private const CIPHER = 'aes-256-gcm';

    private const IV_LENGTH = 12;

    private const TAG_LENGTH = 16;

    public function __construct(
        private string $appSecret,
    ) {
    }

    public function encrypt(string $plaintext, string $hkdfInfo = self::HKDF_INFO_PASSWORD): string
    {
        $key = $this->deriveKey($hkdfInfo);
        $initVector = random_bytes(self::IV_LENGTH);
        $tag = '';
        $ciphertext = openssl_encrypt($plaintext, self::CIPHER, $key, \OPENSSL_RAW_DATA, $initVector, $tag, '', self::TAG_LENGTH);
        if (false === $ciphertext) {
            throw new RuntimeException('Encryption failed.');
        }

        return base64_encode($initVector.$tag.$ciphertext);
    }

    public function decrypt(string $encoded, string $hkdfInfo = self::HKDF_INFO_PASSWORD): string
    {
        $raw = base64_decode($encoded, true);
        if (false === $raw || \strlen($raw) < self::IV_LENGTH + self::TAG_LENGTH) {
            throw new InvalidArgumentException('Invalid ciphertext.');
        }
        $initVector = substr($raw, 0, self::IV_LENGTH);
        $tag = substr($raw, self::IV_LENGTH, self::TAG_LENGTH);
        $ciphertext = substr($raw, self::IV_LENGTH + self::TAG_LENGTH);
        $key = $this->deriveKey($hkdfInfo);
        $plaintext = openssl_decrypt($ciphertext, self::CIPHER, $key, \OPENSSL_RAW_DATA, $initVector, $tag);
        if (false === $plaintext) {
            throw new InvalidArgumentException('Decryption failed.');
        }

        return $plaintext;
    }

    private function deriveKey(string $info): string
    {
        return hash_hkdf('sha256', $this->appSecret, 32, $info, '');
    }
}
