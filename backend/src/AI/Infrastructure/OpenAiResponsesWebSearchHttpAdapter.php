<?php

declare(strict_types=1);

namespace App\AI\Infrastructure;

use App\AI\Domain\Entity\LlmProfile;
use App\AI\Domain\Exception\OpenAiResponsesWebSearchException;
use App\AI\Domain\LlmProfileKind;
use App\AI\Domain\Port\OpenAiResponsesWebSearchPort;
use App\AI\Domain\Repository\LlmProfileRepository;
use App\SharedKernel\Infrastructure\Security\AppSecretStringCipher;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

final readonly class OpenAiResponsesWebSearchHttpAdapter implements OpenAiResponsesWebSearchPort
{
    private const string OPENAI_RESPONSES_URL = 'https://api.openai.com/v1/responses';

    public function __construct(
        private HttpClientInterface $httpClient,
        private LlmProfileRepository $llmProfiles,
        private AppSecretStringCipher $cipher,
    ) {
    }

    public function completeWithWebSearchJson(
        string $systemPrompt,
        string $userPrompt,
        array $responseJsonSchema,
        string $jsonSchemaName,
    ): array {
        $profile = $this->firstEnabledOpenAiProfileWithKey();
        if (null === $profile) {
            throw new OpenAiResponsesWebSearchException('No enabled OpenAI LLM profile with a stored API key is configured. Add one under Settings, LLM models.');
        }

        $apiKey = $this->cipher->decrypt(
            (string) $profile->getApiKeyCipher(),
            AppSecretStringCipher::HKDF_INFO_LLM_API_KEY,
        );
        $model = trim($profile->getModel());
        if ('' === $model) {
            throw new OpenAiResponsesWebSearchException('The selected LLM profile has an empty model name.');
        }

        $trimmedName = trim($jsonSchemaName);
        if ('' === $trimmedName) {
            throw new OpenAiResponsesWebSearchException('JSON schema name must not be empty.');
        }

        $payload = [
            'model' => $model,
            'instructions' => $systemPrompt,
            'input' => $userPrompt,
            'tools' => [
                [
                    'type' => 'web_search',
                ],
            ],
            'tool_choice' => [
                'type' => 'web_search',
            ],
            'text' => [
                'format' => [
                    'type' => 'json_schema',
                    'name' => $trimmedName,
                    'strict' => true,
                    'schema' => $responseJsonSchema,
                ],
            ],
        ];

        try {
            $response = $this->httpClient->request('POST', self::OPENAI_RESPONSES_URL, [
                'headers' => [
                    'Authorization' => 'Bearer '.$apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload,
            ]);
            $status = $response->getStatusCode();
            $body = $response->getContent(false);
        } catch (Throwable $e) {
            throw new OpenAiResponsesWebSearchException('OpenAI request failed: '.$e->getMessage(), 0, $e);
        }

        if ($status < 200 || $status >= 300) {
            throw new OpenAiResponsesWebSearchException('OpenAI returned HTTP '.$status.': '.$this->truncateForMessage($body));
        }

        try {
            /** @var array<string, mixed> $decoded */
            $decoded = json_decode($body, true, 512, \JSON_THROW_ON_ERROR);
        } catch (Throwable $e) {
            throw new OpenAiResponsesWebSearchException('OpenAI response was not valid JSON.', 0, $e);
        }

        if (isset($decoded['error']) && \is_array($decoded['error'])) {
            $msg = isset($decoded['error']['message']) && \is_string($decoded['error']['message'])
                ? $decoded['error']['message']
                : 'Unknown API error';

            throw new OpenAiResponsesWebSearchException('OpenAI error: '.$msg);
        }

        $text = $this->extractStructuredOutputText($decoded);
        if ('' === $text) {
            throw new OpenAiResponsesWebSearchException('OpenAI response contained no assistant text output.');
        }

        try {
            /** @var array<string, mixed> $out */
            $out = json_decode($text, true, 512, \JSON_THROW_ON_ERROR);
        } catch (Throwable $e) {
            throw new OpenAiResponsesWebSearchException('Model output was not valid JSON: '.$this->truncateForMessage($text), 0, $e);
        }

        return $out;
    }

    private function firstEnabledOpenAiProfileWithKey(): ?LlmProfile
    {
        foreach ($this->llmProfiles->findAllOrderedBySortOrder() as $row) {
            if (!$row->isEnabled()) {
                continue;
            }
            if (LlmProfileKind::OpenAi !== $row->getKind()) {
                continue;
            }
            if (!$row->hasStoredApiKey()) {
                continue;
            }

            return $row;
        }

        return null;
    }

    /**
     * @param array<string, mixed> $decoded
     */
    private function extractStructuredOutputText(array $decoded): string
    {
        if (isset($decoded['output_text']) && \is_string($decoded['output_text'])) {
            return trim($decoded['output_text']);
        }

        if (!isset($decoded['output']) || !\is_array($decoded['output'])) {
            return '';
        }

        foreach ($decoded['output'] as $item) {
            if (!\is_array($item)) {
                continue;
            }
            if (($item['type'] ?? null) !== 'message') {
                continue;
            }
            if (($item['role'] ?? null) !== 'assistant') {
                continue;
            }
            if (!isset($item['content']) || !\is_array($item['content'])) {
                continue;
            }
            foreach ($item['content'] as $block) {
                if (!\is_array($block)) {
                    continue;
                }
                $blockType = $block['type'] ?? null;
                if ('output_text' !== $blockType && 'text' !== $blockType) {
                    continue;
                }
                $text = $block['text'] ?? null;
                if (\is_string($text) && '' !== trim($text)) {
                    return trim($text);
                }
            }
        }

        return '';
    }

    private function truncateForMessage(string $raw): string
    {
        $trimmedRaw = trim($raw);
        if (\strlen($trimmedRaw) > 400) {
            return substr($trimmedRaw, 0, 400).'…';
        }

        return $trimmedRaw;
    }
}
