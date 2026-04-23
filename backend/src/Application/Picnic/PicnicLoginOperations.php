<?php

declare(strict_types=1);

namespace App\Application\Picnic;

use App\Application\Picnic\Dto\PostPicnicLoginRequest;
use App\Application\Picnic\Dto\PostPicnicRequestTwoFactorCodeRequest;
use App\Domain\Picnic\Port\PicnicAnonymousAuthenticationPort;
use App\Domain\Picnic\Port\PicnicCredentialCipherPort;
use App\Domain\Picnic\Port\PicnicPendingLoginTokenPort;
use App\Domain\Picnic\Repository\PicnicIntegrationSettingsRepository;
use App\Domain\Picnic\ValueObject\PicnicPasswordLoginResult;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use RuntimeException;

final readonly class PicnicLoginOperations
{
    public function __construct(
        private PicnicIntegrationSettingsRepository $settingsRepo,
        private PicnicCredentialCipherPort $credentialCipher,
        private PicnicPendingLoginTokenPort $pendingLoginToken,
        private PicnicAnonymousAuthenticationPort $picnicAuth,
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function login(PostPicnicLoginRequest $body): array
    {
        $pendingToken = $body->pendingToken;
        $otpCode = $body->otp;
        if (null !== $pendingToken && null !== $otpCode) {
            return $this->loginWithOtp($pendingToken, $otpCode);
        }

        return $this->loginWithUsernamePassword($body);
    }

    /**
     * @return array<string, mixed>
     */
    private function loginWithUsernamePassword(PostPicnicLoginRequest $body): array
    {
        $creds = $this->passwordLoginCredentials($body);
        if (null === $creds) {
            return ['ok' => false, 'message' => 'Username and password are required.'];
        }
        [$username, $countryCode, $password] = $creds;
        try {
            $result = $this->picnicAuth->loginWithPassword($username, $countryCode, $password);
        } catch (RuntimeException $exception) {
            return ['ok' => false, 'message' => $exception->getMessage()];
        }

        return $this->completePasswordLogin($username, $countryCode, $password, $result);
    }

    /**
     * @return array{0: string, 1: string, 2: string}|null
     */
    private function passwordLoginCredentials(PostPicnicLoginRequest $body): ?array
    {
        $username = self::stringOrEmpty($body->username);
        $password = self::stringOrEmpty($body->password);
        if ('' === $username || '' === $password) {
            return null;
        }
        $countryCode = self::stringOrDefault($body->countryCode, 'NL');

        return [$username, $countryCode, $password];
    }

    private static function stringOrEmpty(mixed $value): string
    {
        return \is_string($value) ? $value : '';
    }

    private static function stringOrDefault(mixed $value, string $default): string
    {
        return \is_string($value) ? $value : $default;
    }

    /**
     * @return array<string, mixed>
     */
    private function completePasswordLogin(
        string $username,
        string $countryCode,
        string $password,
        PicnicPasswordLoginResult $result,
    ): array {
        if ($result->secondFactorRequired) {
            $pending = $this->pendingLoginToken->encode($username, $countryCode, $password, $result->authKey);

            return [
                'ok' => true,
                'secondFactorAuthenticationRequired' => true,
                'pendingToken' => $pending,
                'message' => 'Two-factor authentication required.',
            ];
        }
        $this->persistSuccessfulSession($username, $countryCode, $result->authKey);

        return [
            'ok' => true,
            'secondFactorAuthenticationRequired' => false,
            'message' => 'Logged in to Picnic.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function requestTwoFactorCode(PostPicnicRequestTwoFactorCodeRequest $body): array
    {
        $pending = $body->pendingToken;
        $channel = $body->channel;
        if ('' === $pending) {
            return ['ok' => false, 'message' => 'Missing pending token.'];
        }
        try {
            $decoded = $this->pendingLoginToken->decode($pending);
        } catch (InvalidArgumentException $exception) {
            return ['ok' => false, 'message' => $exception->getMessage()];
        }
        try {
            $this->picnicAuth->requestTwoFactorCode($decoded, strtoupper($channel));
        } catch (RuntimeException $exception) {
            return ['ok' => false, 'message' => $exception->getMessage()];
        }

        return ['ok' => true, 'message' => 'Two-factor code requested.'];
    }

    /**
     * @return array<string, mixed>
     */
    private function loginWithOtp(string $pendingToken, string $otp): array
    {
        try {
            $decoded = $this->pendingLoginToken->decode($pendingToken);
        } catch (InvalidArgumentException $exception) {
            return ['ok' => false, 'message' => $exception->getMessage()];
        }
        try {
            $authKey = $this->picnicAuth->verifyTwoFactorCode($decoded, $otp);
        } catch (RuntimeException $exception) {
            return ['ok' => false, 'message' => $exception->getMessage()];
        }
        $this->persistSuccessfulSession($decoded->username, $decoded->countryCode, $authKey);

        return [
            'ok' => true,
            'secondFactorAuthenticationRequired' => false,
            'message' => 'Logged in to Picnic.',
        ];
    }

    private function persistSuccessfulSession(string $username, string $countryCode, string $authKey): void
    {
        $settings = $this->settingsRepo->getSingleton();
        $settings->changeUsername($username);
        $settings->changeCountryCode($countryCode);
        $settings->changeAuthKeyCipher(
            $this->credentialCipher->encryptAuthKeyForStorage($authKey),
        );
        $this->entityManager->flush();
    }
}
