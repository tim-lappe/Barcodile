<?php

declare(strict_types=1);

namespace App\Infrastructure\Picnic;

use App\Domain\Cart\Port\CartProviderAccessException;
use App\Domain\Picnic\Entity\PicnicIntegrationSettings;
use App\Domain\Picnic\Repository\PicnicIntegrationSettingsRepository;
use App\Infrastructure\Shared\Security\AppSecretStringCipher;
use InvalidArgumentException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class PicnicAuthenticatedClientFactory
{
    public function __construct(
        private PicnicIntegrationSettingsRepository $settingsRepo,
        private AppSecretStringCipher $cipher,
        private HttpClientInterface $httpClient,
        #[Autowire('%env(string:PICNIC_API_VERSION)%')]
        private string $defaultApiVersion,
    ) {
    }

    public function createClient(PicnicApiConfig $config): PicnicClient
    {
        $authState = new PicnicAuthState();

        return new PicnicClient($this->httpClient, $config, $authState);
    }

    /**
     * @return array{config: PicnicApiConfig, cacheKey: string}
     */
    public function resolveConfigAndCacheKeyOrThrow(): array
    {
        $settings = $this->settingsRepo->getSingleton();
        $authKey = $this->decryptAuthKeyOrThrow($settings);
        $config = new PicnicApiConfig($settings->getCountryCode(), $this->defaultApiVersion, $authKey, null);

        return [
            'config' => $config,
            'cacheKey' => $this->buildPicnicCacheKey($settings, $authKey),
        ];
    }

    private function decryptAuthKeyOrThrow(PicnicIntegrationSettings $settings): string
    {
        $authCipher = $this->requireAuthCipherText($settings);
        try {
            $authKey = $this->cipher->decrypt($authCipher, AppSecretStringCipher::HKDF_INFO_AUTH_KEY);
        } catch (InvalidArgumentException) {
            throw new CartProviderAccessException('Stored Picnic session could not be decrypted.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        if ('' === $authKey) {
            throw new CartProviderAccessException('Picnic is not connected. Log in under Settings.', Response::HTTP_BAD_REQUEST);
        }

        return $authKey;
    }

    private function requireAuthCipherText(PicnicIntegrationSettings $settings): string
    {
        $authCipher = $settings->getAuthKeyCipher();
        if (null === $authCipher || '' === $authCipher) {
            throw new CartProviderAccessException('Picnic is not connected. Log in under Settings.', Response::HTTP_BAD_REQUEST);
        }

        return $authCipher;
    }

    private function buildPicnicCacheKey(PicnicIntegrationSettings $settings, string $authKey): string
    {
        $cacheKeyParts = [
            $authKey,
            $this->defaultApiVersion,
            $settings->getCountryCode(),
            (string) $settings->getId(),
        ];

        return 'picnic_cart.'.hash('xxh128', implode("\0", $cacheKeyParts));
    }
}
