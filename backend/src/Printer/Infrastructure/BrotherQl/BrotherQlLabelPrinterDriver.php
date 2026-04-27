<?php

declare(strict_types=1);

namespace App\Printer\Infrastructure\BrotherQl;

use App\Printer\Domain\Dto\ColorModePrintSettingOption;
use App\Printer\Domain\Dto\LabelPrinterConnection;
use App\Printer\Domain\Dto\LabelPrintSettingOptions;
use App\Printer\Domain\Dto\LabelPrintSettings;
use App\Printer\Domain\Dto\LabelSizePrintSettingOption;
use App\Printer\Domain\Exception\LabelPrintJobFailedException;
use App\Printer\Domain\Port\LabelPrinterDriver;
use App\Printer\Domain\ValueObject\PrinterDriverCode;
use App\Printer\Domain\ValueObject\PrinterDriverDisplayLabel;
use App\SharedKernel\Domain\Label\LabelSize;
use JsonException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Process;

final class BrotherQlLabelPrinterDriver implements LabelPrinterDriver
{
    private const DRIVER_CODE = 'brother_ql';

    public function __construct(
        private readonly string $projectDir,
        private readonly BrotherQlDiscoveryMapper $discoveryMapper,
        private readonly BrotherQlConnectionValidator $connectionValidator,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function driverCode(): PrinterDriverCode
    {
        return new PrinterDriverCode(self::DRIVER_CODE);
    }

    public function displayLabel(): PrinterDriverDisplayLabel
    {
        return new PrinterDriverDisplayLabel('Brother QL (brother_ql)');
    }

    public function defaultPrintSettings(): LabelPrintSettings
    {
        return BrotherQlPrintSettings::defaults();
    }

    public function printSettingOptions(): LabelPrintSettingOptions
    {
        return new LabelPrintSettingOptions(
            $this->labelSizeOptions(),
            [
                new ColorModePrintSettingOption('black', 'Black only', false),
                new ColorModePrintSettingOption('red_black', 'Red and black', true),
            ],
        );
    }

    public function discover(): array
    {
        $decoded = $this->runPythonJsonArray('discover.py');

        return $this->discoveryMapper->mapRows($decoded);
    }

    public function createConnection(array $connection): LabelPrinterConnection
    {
        $this->connectionValidator->validate($connection);

        return BrotherQlPrinterConnection::fromArray($connection);
    }

    public function createPrintSettings(array $printSettings): LabelPrintSettings
    {
        return BrotherQlPrintSettings::fromArray($printSettings);
    }

    public function printLabelImage(
        LabelPrinterConnection $connection,
        LabelPrintSettings $printSettings,
        LabelSize $labelSize,
        string $pngBytes,
    ): void {
        $brotherQlConnection = $this->brotherQlConnection($connection);
        $settings = $this->brotherQlPrintSettings($printSettings);
        $selectedLabelCode = BrotherQlPrintSettings::labelCodeFor($labelSize);
        $this->logger->info('Brother QL label image print started.', [
            'model' => $brotherQlConnection->model,
            'backend' => $brotherQlConnection->backend,
            'printerIdentifier' => $brotherQlConnection->printerIdentifier,
            'labelSize' => $selectedLabelCode,
            'imageBytes' => \strlen($pngBytes),
        ]);
        $payload = json_encode([
            'connection' => $brotherQlConnection->connectionData(),
            'printSettings' => [
                'labelSize' => $selectedLabelCode,
                'red' => $settings->red,
            ],
            'imageBase64' => base64_encode($pngBytes),
        ], \JSON_THROW_ON_ERROR);
        $this->runPythonScript('print_label_image.py', $payload);
    }

    private function brotherQlConnection(LabelPrinterConnection $connection): BrotherQlPrinterConnection
    {
        if (!$connection instanceof BrotherQlPrinterConnection) {
            throw new LabelPrintJobFailedException('Brother QL connection is required.');
        }

        return $connection;
    }

    private function brotherQlPrintSettings(LabelPrintSettings $printSettings): BrotherQlPrintSettings
    {
        if (!$printSettings instanceof BrotherQlPrintSettings) {
            throw new LabelPrintJobFailedException('Brother QL print settings are required.');
        }

        return $printSettings;
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
     * @return list<LabelSizePrintSettingOption>
     */
    private function labelSizeOptions(): array
    {
        $out = [];
        foreach (BrotherQlPrintSettings::allowedLabelSizes() as $size) {
            $out[] = new LabelSizePrintSettingOption($size, $this->labelSizeLabel($size), BrotherQlPrintSettings::toLabelSize($size));
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

    private function trimOutput(string $output): string
    {
        return substr(trim($output), 0, 4000);
    }
}
