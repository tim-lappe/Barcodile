<?php

declare(strict_types=1);

namespace App\Picnic\Domain\ValueObject;

final readonly class PicnicPasswordLoginResult
{
    public function __construct(
        public bool $secondFactorRequired,
        public string $authKey,
    ) {
    }
}
