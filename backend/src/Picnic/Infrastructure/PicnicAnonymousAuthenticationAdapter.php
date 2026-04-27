<?php

declare(strict_types=1);

namespace App\Picnic\Infrastructure;

use App\Picnic\Domain\Port\PicnicAnonymousAuthenticationPort;
use App\Picnic\Domain\ValueObject\PicnicPasswordLoginResult;
use App\Picnic\Domain\ValueObject\PicnicPendingLoginCredentials;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class PicnicAnonymousAuthenticationAdapter implements PicnicAnonymousAuthenticationPort
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $picnicApiVersion,
    ) {
    }

    public function loginWithPassword(
        string $username,
        string $countryCode,
        string $password,
    ): PicnicPasswordLoginResult {
        $config = new PicnicApiConfig($countryCode, $this->picnicApiVersion, null, null);
        $client = new PicnicClient($this->httpClient, $config, new PicnicAuthState());
        $data = $client->auth->login($username, $password);
        $needs2fa = isset($data['second_factor_authentication_required']) && true === $data['second_factor_authentication_required'];
        $authKey = \is_string($data['authKey'] ?? null) ? $data['authKey'] : '';

        return new PicnicPasswordLoginResult($needs2fa, $authKey);
    }

    public function requestTwoFactorCode(PicnicPendingLoginCredentials $pending, string $channel): void
    {
        $config = new PicnicApiConfig($pending->countryCode, $this->picnicApiVersion, $pending->pendingAuthKey, null);
        $client = new PicnicClient($this->httpClient, $config, new PicnicAuthState());
        $client->auth->generate2FACode(strtoupper($channel));
    }

    public function verifyTwoFactorCode(PicnicPendingLoginCredentials $pending, string $otp): string
    {
        $config = new PicnicApiConfig($pending->countryCode, $this->picnicApiVersion, $pending->pendingAuthKey, null);
        $client = new PicnicClient($this->httpClient, $config, new PicnicAuthState());
        $out = $client->auth->verify2FACode($otp);

        return $out['authKey'];
    }
}
