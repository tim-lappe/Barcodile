<?php

declare(strict_types=1);

namespace App\Scanner\Application\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class PostScannerDeviceRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 512)]
        public string $deviceIdentifier,
        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        public string $name,
    ) {
    }
}
