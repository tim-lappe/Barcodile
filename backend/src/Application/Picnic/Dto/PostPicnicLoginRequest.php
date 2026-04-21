<?php

declare(strict_types=1);

namespace App\Application\Picnic\Dto;

final readonly class PostPicnicLoginRequest
{
    public function __construct(
        public ?string $pendingToken = null,
        public ?string $otp = null,
        public ?string $username = null,
        public ?string $countryCode = null,
        public ?string $password = null,
    ) {
    }
}
