<?php

declare(strict_types=1);

namespace App\AI\Domain\Port;

use App\AI\Domain\Exception\OpenAiResponsesWebSearchException;

interface OpenAiResponsesWebSearchPort
{
    /**
     * @param array<string, mixed> $responseJsonSchema root JSON Schema object with type object
     *
     * @return array<string, mixed>
     *
     * @throws OpenAiResponsesWebSearchException
     */
    public function completeWithWebSearchJson(
        string $systemPrompt,
        string $userPrompt,
        array $responseJsonSchema,
        string $jsonSchemaName,
    ): array;
}
