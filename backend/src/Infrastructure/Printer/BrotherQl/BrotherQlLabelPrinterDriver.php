<?php

declare(strict_types=1);

namespace App\Infrastructure\Printer\BrotherQl;

use App\Domain\Printer\Exception\LabelPrintJobFailedException;
use App\Domain\Printer\Port\LabelPrinterDriver;
use JsonException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Process;

final class BrotherQlLabelPrinterDriver implements LabelPrinterDriver
{
    private const DRIVER_CODE = 'brother_ql';
    private const DEFAULT_PRINT_SETTINGS = ['labelSize' => '62', 'red' => true];
    private const ALLOWED_LABEL_SIZES = [
        '12', '29', '38', '50', '54', '62', '102',
        '17x54', '17x87', '23x23', '29x42', '29x90', '39x48',
        '52x29', '62x29', '62x100', '102x51', '102x152',
        'd12', 'd24', 'd58',
    ];

    public function __construct(
        private readonly string $projectDir,
        private readonly BrotherQlDiscoveryMapper $discoveryMapper,
        private readonly BrotherQlConnectionValidator $connectionValidator,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function driverCode(): string
    {
        return self::DRIVER_CODE;
    }

    public function displayLabel(): string
    {
        return 'Brother QL (brother_ql)';
    }

    public function defaultPrintSettings(): array
    {
        return self::DEFAULT_PRINT_SETTINGS;
    }

    public function printSettingOptions(): array
    {
        return [
            'labelSizes' => $this->labelSizeOptions(),
            'colorModes' => [
                ['value' => 'black', 'label' => 'Black only', 'red' => false],
                ['value' => 'red_black', 'label' => 'Red and black', 'red' => true],
            ],
        ];
    }

    public function discover(): array
    {
        $decoded = $this->runPythonJsonArray('discover.py');

        return $this->discoveryMapper->mapRows($decoded);
    }

    public function assertValidConnection(array $connection): void
    {
        $this->connectionValidator->validate($connection);
    }

    public function printTestLabel(array $connection, array $printSettings): void
    {
        $this->assertValidConnection($connection);
        $this->assertValidPrintSettings($printSettings);
        $this->logger->info('Brother QL test label print started.', [
            'model' => $connection['model'] ?? null,
            'backend' => $connection['backend'] ?? null,
            'printerIdentifier' => $connection['printerIdentifier'] ?? null,
            'labelSize' => $printSettings['labelSize'] ?? null,
            'red' => $printSettings['red'] ?? null,
        ]);
        $payload = json_encode([
            'connection' => $connection,
            'printSettings' => $printSettings,
        ], \JSON_THROW_ON_ERROR);
        $this->runPythonScript('print_test.py', $payload);
    }

    public function printLabelImage(array $connection, array $printSettings, string $pngBytes): void
    {
        $this->assertValidConnection($connection);
        $this->assertValidPrintSettings($printSettings);
        $this->logger->info('Brother QL label image print started.', [
            'model' => $connection['model'] ?? null,
            'backend' => $connection['backend'] ?? null,
            'printerIdentifier' => $connection['printerIdentifier'] ?? null,
            'labelSize' => $printSettings['labelSize'] ?? null,
            'imageBytes' => \strlen($pngBytes),
        ]);
        $payload = json_encode([
            'connection' => $connection,
            'printSettings' => $printSettings,
            'imageBase64' => base64_encode($pngBytes),
        ], \JSON_THROW_ON_ERROR);
        $this->runPythonScript('print_label_image.py', $payload);
    }

    public function assertValidPrintSettings(array $printSettings): void
    {
        $labelSize = $this->stringFrom($printSettings, 'labelSize');
        if (!\in_array($labelSize, self::ALLOWED_LABEL_SIZES, true)) {
            throw new LabelPrintJobFailedException('Unsupported Brother QL label size.');
        }
        if (!isset($printSettings['red']) || !\is_bool($printSettings['red'])) {
            throw new LabelPrintJobFailedException('Brother QL red mode must be true or false.');
        }
    }

    /**
     * @return list<mixed>
     */
    private function runPythonJsonArray(string $script): array
    {
        $stdout = $this->runPythonScript($script, null);
        try {
            $decoded = json_decode($stdout, true, 512, \JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new LabelPrintJobFailedException('Could not parse discovery output.', 0, $e);
        }
        if (!\is_array($decoded) || !array_is_list($decoded)) {
            throw new LabelPrintJobFailedException('Discovery returned invalid JSON.');
        }

        return $decoded;
    }

    private function runPythonScript(string $script, ?string $stdin): string
    {
        $path = $this->scriptPath($script);
        $process = $this->createPythonProcess($path, $stdin);
        $started = microtime(true);
        $this->logger->debug('Brother QL script started.', ['script' => $script]);
        $process->run();

        return $this->outputOrThrow($process, $script, microtime(true) - $started);
    }

    private function scriptPath(string $script): string
    {
        $path = $this->projectDir.'/bin/label_printers/brother_ql/'.$script;
        if (!is_file($path)) {
            throw new LabelPrintJobFailedException('Brother QL script not found: '.$script);
        }

        return $path;
    }

    private function createPythonProcess(string $path, ?string $stdin): Process
    {
        $process = new Process(['python3', $path]);
        $process->setTimeout(120.0);
        if (null !== $stdin) {
            $process->setInput($stdin);
        }

        return $process;
    }

    private function outputOrThrow(Process $process, string $script, float $duration): string
    {
        if ($process->isSuccessful()) {
            $this->logger->info('Brother QL script finished.', [
                'script' => $script,
                'durationMs' => (int) round($duration * 1000),
                'exitCode' => $process->getExitCode(),
                'stderr' => $this->trimOutput($process->getErrorOutput()),
            ]);

            return $process->getOutput();
        }
        $err = trim($process->getErrorOutput().$process->getOutput());
        $this->logger->error('Brother QL script failed.', [
            'script' => $script,
            'durationMs' => (int) round($duration * 1000),
            'exitCode' => $process->getExitCode(),
            'output' => $this->trimOutput($process->getOutput()),
            'stderr' => $this->trimOutput($process->getErrorOutput()),
        ]);

        throw new LabelPrintJobFailedException('' !== $err ? $err : 'Brother QL script failed.');
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function labelSizeOptions(): array
    {
        $out = [];
        foreach (self::ALLOWED_LABEL_SIZES as $size) {
            $out[] = ['value' => $size, 'label' => $this->labelSizeLabel($size)];
        }

        return $out;
    }

    private function labelSizeLabel(string $labelSize): string
    {
        return match ($labelSize) {
            '62' => '62 mm continuous tape',
            '29x90' => '29 x 90 mm address label',
            '102x152' => '102 x 152 mm shipping label',
            default => $labelSize,
        };
    }

    /**
     * @param array<string, mixed> $data
     */
    private function stringFrom(array $data, string $key): string
    {
        if (!isset($data[$key])) {
            return '';
        }
        $raw = $data[$key];

        return \is_string($raw) ? $raw : '';
    }

    private function trimOutput(string $output): string
    {
        return substr(trim($output), 0, 4000);
    }
}
