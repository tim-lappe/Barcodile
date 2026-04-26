<?php

declare(strict_types=1);

namespace App\Infrastructure\Printer\BrotherQl;

use App\Domain\Printer\Exception\LabelPrintJobFailedException;
use App\Domain\Printer\Port\LabelPrinterDriver;
use App\Domain\Printer\ValueObject\DiscoveredPrinterOption;
use JsonException;
use Symfony\Component\Process\Process;

final class BrotherQlLabelPrinterDriver implements LabelPrinterDriver
{
    private const DRIVER_CODE = 'brother_ql';

    public function __construct(
        private readonly string $projectDir,
        private readonly string $environment,
        private readonly BrotherQlDiscoveryMapper $discoveryMapper,
        private readonly BrotherQlConnectionValidator $connectionValidator,
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

    public function discover(): array
    {
        $decoded = $this->runPythonJsonArray('discover.py');
        $mapped = $this->discoveryMapper->mapRows($decoded);

        return $this->withDevSyntheticPrinter($mapped);
    }

    public function assertValidConnection(array $connection): void
    {
        $this->connectionValidator->validate($connection);
    }

    public function printTestLabel(array $connection): void
    {
        $this->assertValidConnection($connection);
        $payload = json_encode($connection, \JSON_THROW_ON_ERROR);
        $this->runPythonScript('print_test.py', $payload);
    }

    /**
     * @param list<DiscoveredPrinterOption> $mapped
     *
     * @return list<DiscoveredPrinterOption>
     */
    private function withDevSyntheticPrinter(array $mapped): array
    {
        if ([] !== $mapped || 'dev' !== $this->environment) {
            return $mapped;
        }

        return [
            new DiscoveredPrinterOption(
                'usb://0x04f9:0x209b',
                'Test Brother QL (dev)',
                [
                    'model' => 'QL-800',
                    'printerIdentifier' => 'usb://0x04f9:0x209b',
                    'backend' => 'pyusb',
                    'labelSize' => '29x90',
                ],
            ),
        ];
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
        $process->run();

        return $this->outputOrThrow($process);
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

    private function outputOrThrow(Process $process): string
    {
        if ($process->isSuccessful()) {
            return $process->getOutput();
        }
        $err = trim($process->getErrorOutput().$process->getOutput());

        throw new LabelPrintJobFailedException('' !== $err ? $err : 'Brother QL script failed.');
    }
}
