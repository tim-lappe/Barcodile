<?php

declare(strict_types=1);

namespace App\Printer\Application\Dto;

use Symfony\Component\Serializer\Attribute\SerializedName;

final readonly class PrintedLabelResponse
{
    public function __construct(
        #[SerializedName('id')]
        public string $resourceId,
        public string $printerDeviceId,
        public string $driverCode,
        #[SerializedName('labelWidthMillimeters')]
        public int $widthMm,
        #[SerializedName('labelHeightMillimeters')]
        public int $heightMm,
        public string $source,
        public string $createdAt,
        public string $imageUrl,
    ) {
    }
}
