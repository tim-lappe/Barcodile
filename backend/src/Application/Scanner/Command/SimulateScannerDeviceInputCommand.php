<?php

declare(strict_types=1);

namespace App\Application\Scanner\Command;

use App\Domain\Scanner\Input\ScannerInputReceiver;
use App\Domain\Scanner\Repository\ScannerDeviceRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'scanner:simulate',
    description: 'Print synthetic evdev key events for a configured scanner device (debugging; does not touch the kernel).',
)]
final class SimulateScannerDeviceInputCommand extends Command
{
    public function __construct(
        private readonly ScannerDeviceRepository $deviceRepository,
        private readonly ScannerInputReceiver $inputReceiver,
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
        $style = new SymfonyStyle($input, $output);
        $rawId = $input->getOption('deviceIdentifier');
        if (!\is_string($rawId)) {
            $style->error('Invalid scanner device id.');

            return Command::FAILURE;
        }
        $rawInput = $input->getArgument('text');
        if (!\is_string($rawInput)) {
            $style->error('Invalid input.');

            return Command::FAILURE;
        }

        $device = $this->deviceRepository->findByDeviceIdentifier($rawId);
        if (null === $device) {
            $style->error('Scanner device not found.');

            return Command::FAILURE;
        }

        $this->inputReceiver->receiveInput($device->getId(), $rawInput);

        return Command::SUCCESS;
    }
}
