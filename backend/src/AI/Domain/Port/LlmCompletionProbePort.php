<?php

declare(strict_types=1);

namespace App\AI\Domain\Port;

use App\AI\Domain\Exception\LlmCompletionProbeException;
use App\AI\Domain\LlmProfileKind;

interface LlmCompletionProbePort
{
    /**
     * @throws LlmCompletionProbeException
     */
    public function probeMinimalCompletion(
        LlmProfileKind $kind,
        string $model,
        string $apiKey,
        ?string $baseUrl,
    ): string;
}
