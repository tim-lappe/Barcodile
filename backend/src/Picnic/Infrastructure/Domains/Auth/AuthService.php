<?php

declare(strict_types=1);

namespace App\Picnic\Infrastructure\Domains\Auth;

use App\Picnic\Infrastructure\PicnicAuthState;
use App\Picnic\Infrastructure\PicnicHttpClient;
use App\Picnic\Infrastructure\PicnicHttpHeaderMode;
use JsonException;
use RuntimeException;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class AuthService
{
    public function __construct(
        private readonly PicnicHttpClient $http,
        private readonly PicnicAuthState $authState,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function login(string $username, string $password): array
    {
        $secret = md5($password);
        $response = $this->http->innerHttpClient()->request('POST', $this->http->apiBaseUrl().'/user/login', [
            'headers' => $this->http->getBaseHeaders(),
            'body' => json_encode(['key' => $username, 'secret' => $secret, 'client_id' => 30100], \JSON_THROW_ON_ERROR),
        ]);

        $statusCode = $response->getStatusCode();
        if ($statusCode < 200 || $statusCode >= 300) {
            $this->throwHttpFailure('Login failed', $statusCode, $response->getContent(false));
        }

        return $this->completeLoginSuccess($response);
    }

    /**
     * @return array<string, mixed>
     */
    private function completeLoginSuccess(ResponseInterface $response): array
    {
        $content = $response->getContent(false);
        /** @var array<string, mixed> $data */
        $data = '' !== $content ? json_decode($content, true, 512, \JSON_THROW_ON_ERROR) : [];

        $headers = $response->getHeaders();
        $authKey = $headers['x-picnic-auth'][0] ?? null;
        if (null === $authKey || '' === $authKey) {
            throw new RuntimeException('Login failed: No auth key received.');
        }

        $this->authState->setAuthKey($authKey);

        return [
            'authKey' => $authKey,
            'second_factor_authentication_required' => $data['second_factor_authentication_required'] ?? null,
            'show_second_factor_authentication_intro' => $data['show_second_factor_authentication_intro'] ?? null,
            'user_id' => $data['user_id'] ?? null,
        ];
    }

    public function generate2FACode(string $channel): mixed
    {
        return $this->http->sendRequest('POST', '/user/2fa/generate', ['channel' => $channel], PicnicHttpHeaderMode::WithPicnicAgent);
    }

    /**
     * @return array{authKey: string}
     */
    public function verify2FACode(string $code): array
    {
        $response = $this->http->innerHttpClient()->request('POST', $this->http->apiBaseUrl().'/user/2fa/verify', [
            'headers' => $this->mergePicnicHeadersWithBase(),
            'body' => json_encode(['otp' => $code], \JSON_THROW_ON_ERROR),
        ]);

        $statusCode = $response->getStatusCode();
        if ($statusCode < 200 || $statusCode >= 300) {
            $this->throwHttpFailure('2FA verification failed', $statusCode, $response->getContent(false));
        }

        $authKey = $this->requireAuthKeyFromResponse($response);
        $this->authState->setAuthKey($authKey);

        return ['authKey' => $authKey];
    }

    public function logout(): mixed
    {
        return $this->http->sendRequest('POST', '/user/logout');
    }

    public function generatePhoneVerificationCode(string $phoneNumber): mixed
    {
        return $this->http->sendRequest('POST', '/user/phone_verification/generate', ['phone_number' => $phoneNumber]);
    }

    public function verifyPhoneNumber(string $phoneNumber, string $code): mixed
    {
        return $this->http->sendRequest('POST', '/user/phone_verification/verify', ['otp' => $code, 'phone_number' => $phoneNumber]);
    }

    /**
     * @return array<string, string>
     */
    private function mergePicnicHeadersWithBase(): array
    {
        $headers = $this->http->getBaseHeaders();
        foreach ($this->http->getPicnicHeaders() as $headerName => $headerValue) {
            $headers[$headerName] = $headerValue;
        }

        return $headers;
    }

    private function requireAuthKeyFromResponse(ResponseInterface $response): string
    {
        $authKey = $response->getHeaders()['x-picnic-auth'][0] ?? null;
        if (null === $authKey || '' === $authKey) {
            throw new RuntimeException('2FA verification failed: No auth key received.');
        }

        return $authKey;
    }

    private function throwHttpFailure(string $prefix, int $statusCode, string $body): never
    {
        try {
            $msg = $this->picnicErrorMessageFromBody($body);
            throw new RuntimeException($prefix.': '.($msg ?? (string) $statusCode));
        } catch (JsonException) {
            throw new RuntimeException($prefix.': '.$statusCode);
        }
    }

    /**
     * @throws JsonException
     */
    private function picnicErrorMessageFromBody(string $body): ?string
    {
        /** @var array<string, mixed> $errorData */
        $errorData = json_decode($body, true, 512, \JSON_THROW_ON_ERROR);
        $error = $errorData['error'] ?? null;
        if (!\is_array($error)) {
            return null;
        }
        $message = $error['message'] ?? null;

        return \is_string($message) ? $message : null;
    }
}
