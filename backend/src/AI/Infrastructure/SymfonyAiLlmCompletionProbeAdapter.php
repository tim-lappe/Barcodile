<?php

declare(strict_types=1);

namespace App\AI\Infrastructure;

use App\AI\Domain\Exception\LlmCompletionProbeException;
use App\AI\Domain\LlmProfileKind;
use App\AI\Domain\Port\LlmCompletionProbePort;
use Symfony\AI\Platform\Bridge\Generic\Factory as GenericPlatformFactory;
use Symfony\AI\Platform\Bridge\OpenAi\Factory as OpenAiPlatformFactory;
use Symfony\AI\Platform\Bridge\OpenAi\Gpt;
use Symfony\AI\Platform\Bridge\OpenAi\ModelCatalog as OpenAiModelCatalog;
use Symfony\AI\Platform\Capability;
use Symfony\AI\Platform\Exception\ExceptionInterface as SymfonyAiException;
use Symfony\AI\Platform\Message\Content\Text;
use Symfony\AI\Platform\Message\MessageBag;
use Symfony\AI\Platform\Message\SystemMessage;
use Symfony\AI\Platform\Message\UserMessage;
use Symfony\AI\Platform\Platform;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

final readonly class SymfonyAiLlmCompletionProbeAdapter implements LlmCompletionProbePort
{
    public function __construct(
        private HttpClientInterface $httpClient,
    ) {
    }

    public function probeMinimalCompletion(
        LlmProfileKind $kind,
        string $model,
        string $apiKey,
        ?string $baseUrl,
    ): string {
        $model = trim($model);
        if ('' === $model) {
            throw new LlmCompletionProbeException('Model name must not be empty.');
        }

        try {
            $platform = match ($kind) {
                LlmProfileKind::OpenAi => OpenAiPlatformFactory::createPlatform(
                    $apiKey,
                    $this->httpClient,
                    new OpenAiModelCatalog([
                        $model => [
                            'class' => Gpt::class,
                            'capabilities' => [
                                Capability::INPUT_MESSAGES,
                                Capability::OUTPUT_TEXT,
                                Capability::OUTPUT_STREAMING,
                            ],
                        ],
                    ]),
                ),
                LlmProfileKind::OpenAiCompatible => $this->createGenericPlatform($apiKey, $baseUrl),
            };

            $messages = new MessageBag(
                new SystemMessage('You only reply with the single word OK.'),
                new UserMessage(new Text('Reply with OK.')),
            );

            $deferred = $platform->invoke($model, $messages, []);
            $content = $deferred->getResult()->getContent();
            if (\is_string($content)) {
                return trim($content);
            }

            return '';
        } catch (SymfonyAiException $e) {
            throw new LlmCompletionProbeException($e->getMessage(), 0, $e);
        } catch (Throwable $e) {
            throw new LlmCompletionProbeException($e->getMessage(), 0, $e);
        }
    }

    private function createGenericPlatform(string $apiKey, ?string $baseUrl): Platform
    {
        $trimmedBase = null !== $baseUrl ? trim($baseUrl) : '';
        if ('' === $trimmedBase) {
            throw new LlmCompletionProbeException('Base URL is required for OpenAI-compatible providers.');
        }

        $key = trim($apiKey);

        return GenericPlatformFactory::createPlatform(
            rtrim($trimmedBase, '/'),
            '' !== $key ? $key : null,
            $this->httpClient,
        );
    }
}
