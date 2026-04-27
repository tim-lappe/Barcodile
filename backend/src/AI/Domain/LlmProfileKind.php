<?php

declare(strict_types=1);

namespace App\AI\Domain;

enum LlmProfileKind: string
{
    case OpenAi = 'openai';
    case OpenAiCompatible = 'openai_compatible';
}
