<?php

declare(strict_types=1);

namespace App\Picnic\Infrastructure;

use JsonException;
use RuntimeException;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

abstract class PicnicHttpClient
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly PicnicApiConfig $config,
        private readonly PicnicAuthState $authState,
    ) {
        if (null !== $this->config->authKey) {
            $this->authState->setAuthKey($this->config->authKey);
        }
    }

    public function innerHttpClient(): HttpClientInterface
    {
        return $this->httpClient;
    }

    public function apiBaseUrl(): string
    {
        return rtrim($this->config->resolveUrl(), '/');
    }

    public function storefrontOrigin(): string
    {
        $parts = explode('/api/', $this->apiBaseUrl(), 2);

        return $parts[0];
    }

    /**
     * @return array<string, string>
     */
    public function getBaseHeaders(): array
    {
        $headers = [
            'User-Agent' => 'okhttp/3.12.2',
            'Content-Type' => 'application/json; charset=UTF-8',
        ];
        $authKey = $this->authState->getAuthKey();
        if (null !== $authKey) {
            $headers['x-picnic-auth'] = $authKey;
        }

        return $headers;
    }

    /**
     * @return array<string, string>
     */
    public function getPicnicHeaders(): array
    {
        return [
            'x-picnic-agent' => '30100;1.15.232-15154',
            'x-picnic-did' => '3C417201548B2E3B',
        ];
    }

    /**
     * @param array<int|string, mixed>|null $data
     */
    public function sendRequest(
        string $method,
        string $path,
        ?array $data = null,
        PicnicHttpHeaderMode $headerMode = PicnicHttpHeaderMode::Base,
        PicnicHttpBodyMode $bodyMode = PicnicHttpBodyMode::Json,
    ): mixed {
        $url = $this->resolveRequestUrl($path);
        $headers = $this->getBaseHeaders();
        if (PicnicHttpHeaderMode::WithPicnicAgent === $headerMode) {
            foreach ($this->getPicnicHeaders() as $k => $v) {
                $headers[$k] = $v;
            }
        }

        $options = [
            'headers' => $headers,
        ];
        if (null !== $data) {
            $options['body'] = json_encode($data, \JSON_THROW_ON_ERROR);
        }

        $response = $this->httpClient->request($method, $url, $options);

        return $this->processResponse($response, $bodyMode);
    }

    private function resolveRequestUrl(string $path): string
    {
        if (1 === preg_match('#^https?://#i', $path)) {
            return $path;
        }

        return $this->apiBaseUrl().$path;
    }

    private function processResponse(ResponseInterface $response, PicnicHttpBodyMode $bodyMode): mixed
    {
        $statusCode = $response->getStatusCode();
        if ($statusCode < 200 || $statusCode >= 300) {
            $this->throwForFailedResponse($response);
        }

        return $this->decodeSuccessfulBody($response, $bodyMode);
    }

    private function decodeSuccessfulBody(ResponseInterface $response, PicnicHttpBodyMode $bodyMode): mixed
    {
        if (PicnicHttpBodyMode::Raw === $bodyMode) {
            return $response->getContent(false);
        }

        $content = $response->getContent(false);
        if ('' === $content) {
            return null;
        }

        return json_decode($content, true, 512, \JSON_THROW_ON_ERROR);
    }

    private function throwForFailedResponse(ResponseInterface $response): never
    {
        $body = $response->getContent(false);
        try {
            $message = $this->parseErrorMessageFromJson($body);
            throw new RuntimeException($message ?? (string) $response->getStatusCode());
        } catch (JsonException) {
            $suffix = '' !== $body ? ' - '.$body : '';
            throw new RuntimeException($response->getStatusCode().$suffix);
        }
    }

    /**
     * @throws JsonException
     */
    private function parseErrorMessageFromJson(string $body): ?string
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
