<?php

declare(strict_types=1);

namespace App\Infrastructure\Picnic;

use App\Domain\Picnic\Port\PicnicPendingLoginTokenPort;
use App\Domain\Picnic\ValueObject\PicnicPendingLoginCredentials;
use App\Infrastructure\Shared\Security\AppSecretStringCipher;
use InvalidArgumentException;
use JsonException;

final readonly class PicnicPendingLoginTokenCodec implements PicnicPendingLoginTokenPort
{
    public function __construct(private AppSecretStringCipher $cipher)
    {
    }

    public function encode(
        string $username,
        string $countryCode,
        string $password,
        string $pendingAuthKey,
    ): string {
        $payload = json_encode([
            'v' => 1,
            'exp' => time() + 600,
            'username' => $username,
            'countryCode' => $countryCode,
            'password' => $password,
            'pendingAuthKey' => $pendingAuthKey,
        ], \JSON_THROW_ON_ERROR);

        return $this->cipher->encrypt($payload, AppSecretStringCipher::HKDF_INFO_PENDING_LOGIN);
    }

    public function decode(string $token): PicnicPendingLoginCredentials
    {
        $json = $this->cipher->decrypt($token, AppSecretStringCipher::HKDF_INFO_PENDING_LOGIN);
        $data = $this->decodedPayloadArray($json);
        $this->assertSupportedVersion($data);
        $this->assertNotExpired($data);
        $row = $this->credentialsFromPayload($data);

        return new PicnicPendingLoginCredentials(
            $row['username'],
            $row['countryCode'],
            $row['password'],
            $row['pendingAuthKey'],
        );
    }

    /**
     * @return array<mixed>
     */
    private function decodedPayloadArray(string $json): array
    {
        try {
            $decoded = json_decode($json, true, 512, \JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new InvalidArgumentException('Invalid pending login token.');
        }
        if (!\is_array($decoded)) {
            throw new InvalidArgumentException('Invalid pending login token.');
        }

        return $decoded;
    }

    /**
     * @param array<mixed> $data
     */
    private function assertSupportedVersion(array $data): void
    {
        if (($data['v'] ?? null) !== 1) {
            throw new InvalidArgumentException('Unsupported pending login token.');
        }
    }

    /**
     * @param array<mixed> $data
     */
    private function assertNotExpired(array $data): void
    {
        $expSeconds = $this->requireExpirySeconds($data);
        if ($expSeconds < time()) {
            throw new InvalidArgumentException('Pending login token has expired. Start login again.');
        }
    }

    /**
     * @param array<mixed> $data
     */
    private function requireExpirySeconds(array $data): int
    {
        $exp = $data['exp'] ?? null;
        if (\is_int($exp)) {
            return $exp;
        }
        if (\is_float($exp)) {
            return (int) $exp;
        }
        throw new InvalidArgumentException('Invalid pending login token.');
    }

    /**
     * @param array<mixed> $data
     *
     * @return array{username: string, countryCode: string, password: string, pendingAuthKey: string}
     */
    private function credentialsFromPayload(array $data): array
    {
        return [
            'username' => $this->stringField($data, 'username'),
            'countryCode' => $this->stringField($data, 'countryCode'),
            'password' => $this->stringField($data, 'password'),
            'pendingAuthKey' => $this->stringField($data, 'pendingAuthKey'),
        ];
    }

    /**
     * @param array<mixed> $data
     */
    private function stringField(array $data, string $key): string
    {
        $value = $data[$key] ?? null;
        if (!\is_string($value)) {
            throw new InvalidArgumentException('Invalid pending login token payload.');
        }

        return $value;
    }
}
