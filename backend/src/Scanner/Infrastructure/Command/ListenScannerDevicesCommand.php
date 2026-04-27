<?php

declare(strict_types=1);

namespace App\Scanner\Infrastructure\Command;

use App\Scanner\Domain\Entity\ScannerDevice;
use App\Scanner\Domain\Input\ScanLineKeyAccumulator;
use App\Scanner\Domain\Input\ScanLineKeyAccumulatorFactory;
use App\Scanner\Domain\Input\ScannerInputReceiver;
use App\Scanner\Domain\Repository\ScannerDeviceRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @phpstan-type ListenEntry array{handle: resource, device: ScannerDevice, buffer: string, keys: ScanLineKeyAccumulator}
 */
#[AsCommand(
    name: 'scanner:listen',
    description: 'Read keyboard-wedge scanner input from configured evdev devices, print each scan, and record it like scanner:simulate (Linux).',
)]
final class ListenScannerDevicesCommand extends Command
{
    private const int DEVICE_CONFIGURATION_POLL_SECONDS = 5;

    public function __construct(
        private readonly ScannerDeviceRepository $deviceRepository,
        private readonly ScannerInputReceiver $scannerInputReceiver,
        private readonly ScanLineKeyAccumulatorFactory $accumulatorFactory,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $style = new SymfonyStyle($input, $output);
        $devices = $this->waitForConfiguredDevices($style);

        $state = $this->openEvdevStreams($style, $devices);
        if (null === $state) {
            return Command::FAILURE;
        }

        $style->writeln(\sprintf('Listening on %d device(s). Press Ctrl+C to stop.', \count($state)));
        $this->loopEvdev($style, $state);
        $this->closeEvdevStreams($state);

        return Command::SUCCESS;
    }

    /**
     * @return list<ScannerDevice>
     */
    private function waitForConfiguredDevices(SymfonyStyle $style): array
    {
        while (true) {
            $devices = $this->deviceRepository->findAllOrderedByName();
            if ([] !== $devices) {
                return $devices;
            }
            $style->warning(\sprintf(
                'No scanner devices configured. Checking again in %d seconds.',
                self::DEVICE_CONFIGURATION_POLL_SECONDS,
            ));
            sleep(self::DEVICE_CONFIGURATION_POLL_SECONDS);
        }
    }

    /**
     * @param list<ScannerDevice> $devices
     *
     * @return (list<ListenEntry>)|null
     */
    private function openEvdevStreams(SymfonyStyle $style, array $devices): ?array
    {
        $state = [];
        foreach ($devices as $device) {
            $path = $device->getDeviceIdentifier();
            $handle = fopen($path, 'r');
            if (false === $handle) {
                $style->error(\sprintf('Cannot open %s (%s).', $path, $device->getName()));

                continue;
            }
            stream_set_blocking($handle, false);
            $state[] = [
                'handle' => $handle,
                'device' => $device,
                'buffer' => '',
                'keys' => $this->accumulatorFactory->create(),
            ];
        }
        if ([] === $state) {
            $style->error('No device streams could be opened.');

            return null;
        }

        return $state;
    }

    /**
     * @phpstan-param list<ListenEntry> $state
     *
     * @SuppressWarnings("PHPMD.CyclomaticComplexity")
     */
    private function loopEvdev(SymfonyStyle $style, array &$state): void
    {
        while (true) {
            $read = [];
            foreach ($state as $row) {
                $read[] = $row['handle'];
            }
            $readyCount = $this->selectFirstReadableSet($read);
            if (false === $readyCount) {
                break;
            }
            if (0 === $readyCount) {
                continue;
            }
            $this->drainReadyStreams($style, $state, $read);
        }
    }

    /**
     * @param list<resource> $readyOnly
     *
     * @phpstan-param list<ListenEntry> $state
     *
     * @SuppressWarnings("PHPMD.CyclomaticComplexity")
     */
    private function drainReadyStreams(SymfonyStyle $style, array &$state, array $readyOnly): void
    {
        foreach ($state as $index => $row) {
            if (!\in_array($row['handle'], $readyOnly, true)) {
                continue;
            }
            $entry = $state[$index];
            $chunk = fread($entry['handle'], 4096);
            if (!\is_string($chunk) || '' === $chunk) {
                continue;
            }
            $entry['buffer'] .= $chunk;
            while (\strlen($entry['buffer']) >= 24) {
                $eventChunk = substr($entry['buffer'], 0, 24);
                $entry['buffer'] = substr($entry['buffer'], 24);
                $this->processKeyEvent($style, $entry['device'], $entry['keys'], $eventChunk);
            }
            $state[$index] = $entry;
        }
    }

    /**
     * @param list<resource> $read
     */
    private function selectFirstReadableSet(array &$read): int|false
    {
        $write = null;
        $except = null;
        set_error_handler(static function (): true {
            return true;
        });
        try {
            return stream_select($read, $write, $except, 0, 200000);
        } finally {
            restore_error_handler();
        }
    }

    /**
     * @phpstan-param list<ListenEntry> $state
     */
    private function closeEvdevStreams(array $state): void
    {
        foreach ($state as $row) {
            if (\is_resource($row['handle'])) {
                fclose($row['handle']);
            }
        }
    }

    private function processKeyEvent(
        SymfonyStyle $style,
        ScannerDevice $device,
        ScanLineKeyAccumulator $keys,
        string $event,
    ): void {
        $parts = $this->unpackEvdevEvent($event);
        if (null === $parts) {
            return;
        }
        $done = $keys->process(
            $this->intFromUnpack($parts['type']),
            $this->intFromUnpack($parts['code']),
            $this->intFromUnpack($parts['value']),
        );
        if (null === $done || '' === $done) {
            return;
        }
        $style->writeln($done);
        $this->scannerInputReceiver->receiveInput($device->getId(), $done);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function unpackEvdevEvent(string $event): ?array
    {
        $raw = unpack('qsec/qusec/vtype/vcode/lvalue', $event);
        if (!\is_array($raw) || !isset($raw['type'], $raw['code'], $raw['value'])) {
            return null;
        }

        return [
            'type' => $raw['type'],
            'code' => $raw['code'],
            'value' => $raw['value'],
        ];
    }

    private function intFromUnpack(mixed $packedValue): int
    {
        return match (true) {
            \is_int($packedValue) => $packedValue,
            \is_float($packedValue) => (int) $packedValue,
            \is_string($packedValue) && is_numeric($packedValue) => (int) $packedValue,
            default => 0,
        };
    }
}
