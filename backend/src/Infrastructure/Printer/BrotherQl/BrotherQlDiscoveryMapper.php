<?php

declare(strict_types=1);

namespace App\Infrastructure\Printer\BrotherQl;

use App\Domain\Printer\ValueObject\DiscoveredPrinterOption;

final class BrotherQlDiscoveryMapper
{
    private const DEFAULT_PRINT_SETTINGS = ['labelSize' => '62', 'red' => true];

    /**
     * @param list<mixed> $decoded
     *
     * @return list<DiscoveredPrinterOption>
     */
    public function mapRows(array $decoded): array
    {
        $out = [];
        foreach ($decoded as $row) {
            $option = $this->mapOneRow($row);
            if ($option instanceof DiscoveredPrinterOption) {
                $out[] = $option;
            }
        }

        return $out;
    }

    private function mapOneRow(mixed $row): ?DiscoveredPrinterOption
    {
        if (!\is_array($row)) {
            return null;
        }
        $triple = $this->readIdentifierLabelBackend($row);
        if (null === $triple) {
            return null;
        }
        [$ident, $label, $backend] = $triple;

        return new DiscoveredPrinterOption(
            $ident,
            $label,
            $this->suggestedConnection($ident, $backend),
            self::DEFAULT_PRINT_SETTINGS,
        );
    }

    /**
     * @param array<mixed, mixed> $row
     *
     * @return array{0: string, 1: string, 2: string}|null
     */
    private function readIdentifierLabelBackend(array $row): ?array
    {
        $ident = $this->optionalNonEmptyString($row, 'deviceIdentifier');
        $label = $this->optionalNonEmptyString($row, 'label');
        $backend = $this->optionalNonEmptyString($row, 'backend');
        if (null === $ident || null === $label || null === $backend) {
            return null;
        }

        return [$ident, $label, $backend];
    }

    /**
     * @param array<mixed, mixed> $row
     */
    private function optionalNonEmptyString(array $row, string $key): ?string
    {
        if (!isset($row[$key]) || !\is_string($row[$key])) {
            return null;
        }
        $value = $row[$key];

        return '' !== $value ? $value : null;
    }

    /**
     * @return array<string, string>
     */
    private function suggestedConnection(string $printerIdentifier, string $backend): array
    {
        return [
            'model' => 'QL-800',
            'printerIdentifier' => $printerIdentifier,
            'backend' => $backend,
        ];
    }
}
