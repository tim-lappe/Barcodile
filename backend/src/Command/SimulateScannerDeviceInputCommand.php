<?php

declare(strict_types=1);

namespace App\Command;

use App\Domain\Scanner\Entity\ScannerDevice;
use App\Domain\Scanner\Entity\ScannerDeviceId;
use App\Domain\Scanner\Repository\ScannerDeviceRepository;
use App\Infrastructure\Scanner\EvdevConsoleLineFormatter;
use App\Infrastructure\Scanner\EvdevInputEventPacker;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'scanner-devices:simulate',
    description: 'Print synthetic evdev key events for a configured scanner device (debugging; does not touch the kernel).',
)]
final class SimulateScannerDeviceInputCommand extends Command
{
    private const EV_SYN = 0;

    private const EV_KEY = 1;

    private const SYN_REPORT = 0;

    public function __construct(
        private readonly ScannerDeviceRepository $scannerDeviceRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('scannerDeviceId', InputArgument::REQUIRED, 'UUID of the scanner device row')
            ->addArgument('input', InputArgument::REQUIRED, 'Characters to emit as key press/release sequences')
            ->addOption('binary', 'b', InputOption::VALUE_NONE, 'Write raw 24-byte input_event structs to stdout instead of text lines');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $rawId = $input->getArgument('scannerDeviceId');
        if (!\is_string($rawId)) {
            $io->error('Invalid scanner device id.');

            return Command::FAILURE;
        }
        $rawInput = $input->getArgument('input');
        if (!\is_string($rawInput)) {
            $io->error('Invalid input.');

            return Command::FAILURE;
        }
        if ('' === $rawInput) {
            $io->error('Input must not be empty.');

            return Command::FAILURE;
        }
        $scannerDeviceId = ScannerDeviceId::fromString($rawId);
        $device = $this->scannerDeviceRepository->find($scannerDeviceId);
        if (null === $device) {
            $io->error('Scanner device not found.');

            return Command::FAILURE;
        }

        $text = $rawInput;

        $binary = (bool) $input->getOption('binary');
        $skipped = 0;

        foreach (preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $char) {
            $code = self::keycodeForChar($char);
            if (null === $code) {
                ++$skipped;

                continue;
            }
            self::emitKeyCycle($device, $code, $binary, $io, $output);
        }

        if ($skipped > 0) {
            $io->note(sprintf('Skipped %d character(s) with no key mapping.', $skipped));
        }

        return Command::SUCCESS;
    }

    private static function emitKeyCycle(
        ScannerDevice $device,
        int $code,
        bool $binary,
        SymfonyStyle $io,
        OutputInterface $output,
    ): void {
        $pairs = [
            [self::EV_KEY, $code, 1],
            [self::EV_SYN, self::SYN_REPORT, 0],
            [self::EV_KEY, $code, 0],
            [self::EV_SYN, self::SYN_REPORT, 0],
        ];
        foreach ($pairs as [$type, $eventCode, $value]) {
            if ($binary) {
                $output->write(EvdevInputEventPacker::pack($type, $eventCode, $value), false);

                continue;
            }
            $io->writeln(EvdevConsoleLineFormatter::formatLine($device, $type, $eventCode, $value));
        }
    }

    private static function keycodeForChar(string $char): ?int
    {
        $lower = mb_strtolower($char, 'UTF-8');

        return match ($lower) {
            '0' => 11,
            '1' => 2,
            '2' => 3,
            '3' => 4,
            '4' => 5,
            '5' => 6,
            '6' => 7,
            '7' => 8,
            '8' => 9,
            '9' => 10,
            'a' => 30,
            'b' => 48,
            'c' => 46,
            'd' => 32,
            'e' => 18,
            'f' => 33,
            'g' => 34,
            'h' => 35,
            'i' => 23,
            'j' => 36,
            'k' => 37,
            'l' => 38,
            'm' => 50,
            'n' => 49,
            'o' => 24,
            'p' => 25,
            'q' => 16,
            'r' => 19,
            's' => 31,
            't' => 20,
            'u' => 22,
            'v' => 47,
            'w' => 17,
            'x' => 45,
            'y' => 21,
            'z' => 44,
            ' ' => 57,
            "\t" => 15,
            "\n", "\r" => 28,
            '-' => 12,
            '=' => 13,
            '[' => 26,
            ']' => 27,
            '\\' => 43,
            ';' => 39,
            '\'' => 40,
            '`' => 41,
            ',' => 51,
            '.' => 52,
            '/' => 53,
            default => null,
        };
    }
}
