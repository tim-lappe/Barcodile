<?php

declare(strict_types=1);

namespace App\Printer\Domain\Service;

use App\Printer\Domain\Exception\LabelPrintJobFailedException;
use App\SharedKernel\Domain\Label\LabelContent;
use App\SharedKernel\Domain\Label\LabelSize;

final readonly class LabelSizeSelector
{
    private const QR_CODE_MINIMUM_WIDTH_MM = 62;
    private const QR_CODE_MINIMUM_HEIGHT_MM = 29;
    private const TEXT_MINIMUM_WIDTH_MM = 29;
    private const TEXT_MINIMUM_HEIGHT_MM = 29;

    /**
     * @param list<LabelSize> $availableSizes
     */
    public function select(LabelContent $content, array $availableSizes): LabelSize
    {
        $requiredSize = $this->requiredSize($content);
        $matchingSizes = array_filter(
            $availableSizes,
            static fn (LabelSize $labelSize): bool => $labelSize->fits($requiredSize),
        );
        usort(
            $matchingSizes,
            static fn (LabelSize $left, LabelSize $right): int => $left->area() <=> $right->area(),
        );
        $selected = reset($matchingSizes);
        if (!$selected instanceof LabelSize) {
            throw new LabelPrintJobFailedException('No fitting label size is available.');
        }

        return $selected;
    }

    private function requiredSize(LabelContent $content): LabelSize
    {
        if ($content->isQrCode()) {
            return new LabelSize(self::QR_CODE_MINIMUM_WIDTH_MM, self::QR_CODE_MINIMUM_HEIGHT_MM);
        }
        if ($content->isText()) {
            return new LabelSize(self::TEXT_MINIMUM_WIDTH_MM, self::TEXT_MINIMUM_HEIGHT_MM);
        }

        throw new LabelPrintJobFailedException('Unsupported label content.');
    }
}
