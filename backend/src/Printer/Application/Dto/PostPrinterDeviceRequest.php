<?php

declare(strict_types=1);

namespace App\Printer\Application\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class PostPrinterDeviceRequest
{
    /**
     * @param array<string, mixed> $connection
     * @param array<string, mixed> $printSettings
     */
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 64)]
        public string $driverCode,
        #[Assert\Type('array')]
        public array $connection,
        #[Assert\Type('array')]
        public array $printSettings,
        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        public string $name,
    ) {
    }
}
