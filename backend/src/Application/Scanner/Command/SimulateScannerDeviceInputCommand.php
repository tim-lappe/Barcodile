<?php

declare(strict_types=1);

namespace App\Application\Scanner\Command;

use App\Domain\Scanner\Repository\ScannerDeviceRepository;
use App\Domain\Scanner\Input\ScannerInputReceiver;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'scanner:simulate',
    description: 'Print synthetic evdev key events for a configured scanner device (debugging; does not touch the kernel).',
)]
final class SimulateScannerDeviceInputCommand extends Command
{
    public function __construct(
        private readonly ScannerDeviceRepository $scannerDeviceRepository,
        private readonly ScannerInputReceiver $scannerInputReceiver,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('deviceIdentifier', 'd', InputOption::VALUE_REQUIRED, 'UUID of the scanner device row', 'test-device')
            ->addArgument('text', InputArgument::REQUIRED, 'Characters to emit as key press/release sequences');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $rawId = $input->getOption('deviceIdentifier');
        if (!\is_string($rawId)) {
            $io->error('Invalid scanner device id.');

            return Command::FAILURE;
        }
        $rawInput = $input->getArgument('text');
        if (!\is_string($rawInput)) {
            $io->error('Invalid input.');

            return Command::FAILURE;
        }

        $device = $this->scannerDeviceRepository->findByDeviceIdentifier($rawId);
        if (null === $device) {
            $io->error('Scanner device not found.');

            return Command::FAILURE;
        }

        $text = $rawInput;

        $this->scannerInputReceiver->receiveInput($device->getId(), $text);

        return Command::SUCCESS;
    }
}
