<?php

declare(strict_types=1);

namespace App\Application\Picnic;

use App\Application\Picnic\Dto\PostPicnicLoginRequest;
use App\Application\Picnic\Dto\PostPicnicRequestTwoFactorCodeRequest;
use App\Domain\Picnic\Repository\PicnicIntegrationSettingsRepository;
use App\Infrastructure\Picnic\PicnicApiConfig;
use App\Infrastructure\Picnic\PicnicAuthState;
use App\Infrastructure\Picnic\PicnicClient;
use App\Infrastructure\Picnic\PicnicPendingLoginTokenCodec;
use App\Infrastructure\Shared\Security\AppSecretStringCipher;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class PicnicLoginOperations
{
    public function __construct(
        private PicnicIntegrationSettingsRepository $settingsRepo,
        private AppSecretStringCipher $secretCipher,
        private PicnicPendingLoginTokenCodec $pendingLoginCodec,
        private HttpClientInterface $httpClient,
        private EntityManagerInterface $entityManager,
        #[\Symfony\Component\DependencyInjection\Attribute\Autowire('%env(string:PICNIC_API_VERSION)%')]
        private string $picnicApiVersion,
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
        $config = new PicnicApiConfig($countryCode, $this->picnicApiVersion, null, null);
        $client = new PicnicClient($this->httpClient, $config, new PicnicAuthState());
        try {
            $data = $client->auth->login($username, $password);
        } catch (RuntimeException $exception) {
            return ['ok' => false, 'message' => $exception->getMessage()];
        }

        return $this->completePasswordLogin($username, $countryCode, $password, $data);
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
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    private function completePasswordLogin(string $username, string $countryCode, string $password, array $data): array
    {
        $needs2fa = isset($data['second_factor_authentication_required']) && true === $data['second_factor_authentication_required'];
        $authKey = \is_string($data['authKey'] ?? null) ? $data['authKey'] : '';
        if ($needs2fa) {
            $pending = $this->pendingLoginCodec->encode($username, $countryCode, $password, $authKey);

            return [
                'ok' => true,
                'secondFactorAuthenticationRequired' => true,
                'pendingToken' => $pending,
                'message' => 'Two-factor authentication required.',
            ];
        }
        $this->persistSuccessfulSession($username, $countryCode, $authKey);

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
            $decoded = $this->pendingLoginCodec->decode($pending);
        } catch (InvalidArgumentException $exception) {
            return ['ok' => false, 'message' => $exception->getMessage()];
        }
        $config = new PicnicApiConfig($decoded['countryCode'], $this->picnicApiVersion, $decoded['pendingAuthKey'], null);
        $client = new PicnicClient($this->httpClient, $config, new PicnicAuthState());
        try {
            $client->auth->generate2FACode(strtoupper($channel));
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
            $decoded = $this->pendingLoginCodec->decode($pendingToken);
        } catch (InvalidArgumentException $exception) {
            return ['ok' => false, 'message' => $exception->getMessage()];
        }
        $config = new PicnicApiConfig($decoded['countryCode'], $this->picnicApiVersion, $decoded['pendingAuthKey'], null);
        $client = new PicnicClient($this->httpClient, $config, new PicnicAuthState());
        try {
            $out = $client->auth->verify2FACode($otp);
        } catch (RuntimeException $exception) {
            return ['ok' => false, 'message' => $exception->getMessage()];
        }
        $this->persistSuccessfulSession($decoded['username'], $decoded['countryCode'], $out['authKey']);

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
            $this->secretCipher->encrypt($authKey, AppSecretStringCipher::HKDF_INFO_AUTH_KEY),
        );
        $this->entityManager->flush();
    }
}
