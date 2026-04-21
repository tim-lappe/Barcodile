<?php

declare(strict_types=1);

namespace App\Command;

use App\Domain\Scanner\Entity\ScannerDevice;
use App\Domain\Scanner\Repository\ScannerDeviceRepository;
use App\Infrastructure\Scanner\EvdevConsoleLineFormatter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'scanner-devices:listen',
    description: 'Open all configured scanner devices and print evdev events to stdout (Linux).',
)]
final class ListenScannerDevicesCommand extends Command
{
    public function __construct(
        private readonly ScannerDeviceRepository $scannerDeviceRepository,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $devices = $this->scannerDeviceRepository->findAllOrderedByName();
        if ($devices === []) {
            $io->warning('No scanner devices configured.');

            return Command::SUCCESS;
        }

        $state = [];
        foreach ($devices as $device) {
            $path = $device->getDeviceIdentifier();
            $handle = @fopen($path, 'rb');
            if (!\is_resource($handle)) {
                $io->error(sprintf('Cannot open %s (%s).', $path, $device->getName()));

                continue;
            }
            stream_set_blocking($handle, false);
            $state[] = [
                'handle' => $handle,
                'device' => $device,
                'buffer' => '',
            ];
        }

        if ($state === []) {
            $io->error('No device streams could be opened.');

            return Command::FAILURE;
        }

        $io->writeln(sprintf('Listening on %d device(s). Press Ctrl+C to stop.', \count($state)));

        while (true) {
            $read = array_map(static fn (array $s) => $s['handle'], $state);
            $write = null;
            $except = null;
            $n = @stream_select($read, $write, $except, 0, 200000);
            if (false === $n) {
                break;
            }
            if (0 === $n) {
                continue;
            }

            foreach ($state as $i => $s) {
                if (!\in_array($s['handle'], $read, true)) {
                    continue;
                }
                $chunk = fread($s['handle'], 4096);
                if (!\is_string($chunk) || '' === $chunk) {
                    continue;
                }
                $state[$i]['buffer'] .= $chunk;
                while (\strlen($state[$i]['buffer']) >= 24) {
                    $event = substr($state[$i]['buffer'], 0, 24);
                    $state[$i]['buffer'] = substr($state[$i]['buffer'], 24);
                    $this->printEvent($io, $s['device'], $event);
                }
            }
        }

        foreach ($state as $s) {
            if (\is_resource($s['handle'])) {
                fclose($s['handle']);
            }
        }

        return Command::SUCCESS;
    }

    private function printEvent(SymfonyStyle $io, ScannerDevice $device, string $event): void
    {
        $parts = unpack('qsec/qusec/vtype/vcode/lvalue', $event);
        if (!\is_array($parts) || !isset($parts['type'], $parts['code'], $parts['value'])) {
            return;
        }
        $io->writeln(EvdevConsoleLineFormatter::formatLine(
            $device,
            $this->intFromUnpack($parts['type']),
            $this->intFromUnpack($parts['code']),
            $this->intFromUnpack($parts['value']),
        ));
    }

    private function intFromUnpack(mixed $v): int
    {
        if (\is_int($v)) {
            return $v;
        }
        if (\is_float($v)) {
            return (int) $v;
        }
        if (\is_string($v) && is_numeric($v)) {
            return (int) $v;
        }

        return 0;
    }
}
