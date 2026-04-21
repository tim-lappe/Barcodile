<?php

declare(strict_types=1);

namespace App\Application\Picnic\Dto;

final readonly class PostPicnicRequestTwoFactorCodeRequest
{
    public function __construct(
        public string $pendingToken = '',
        public string $channel = 'SMS',
    ) {
    }
}
