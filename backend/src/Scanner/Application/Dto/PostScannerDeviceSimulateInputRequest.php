<?php

declare(strict_types=1);

namespace App\Scanner\Application\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class PostScannerDeviceSimulateInputRequest
{
    public function __construct(
        #[Assert\NotBlank]
        public string $text,
    ) {
    }
}
