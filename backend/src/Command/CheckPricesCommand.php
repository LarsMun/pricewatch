<?php

namespace App\Command;

use App\Repository\ProductWatchRepository;
use App\Service\PriceCheckService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:check-prices',
    description: 'Check prices for watches that are due',
)]
class CheckPricesCommand extends Command
{
    public function __construct(
        private ProductWatchRepository $watchRepository,
        private PriceCheckService $priceCheckService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('limit', 'l', InputOption::VALUE_REQUIRED, 'Maximum number of watches to check', 100)
            ->addOption('watch', 'w', InputOption::VALUE_REQUIRED, 'Check a specific watch by ID')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $watchId = $input->getOption('watch');
        
        if ($watchId) {
            // Check specific watch
            $watch = $this->watchRepository->find((int) $watchId);
            
            if (!$watch) {
                $io->error("Watch #$watchId not found");
                return Command::FAILURE;
            }

            $io->info("Checking watch #{$watch->getId()}: {$watch->getUrl()}");
            $result = $this->priceCheckService->check($watch);
            
            if ($result->wasSuccessful()) {
                $io->success("Price: {$result->getPrice()} (raw: {$result->getRawText()})");
            } else {
                $io->error("Failed: {$result->getErrorMessage()}");
            }

            return Command::SUCCESS;
        }

        // Check all due watches
        $limit = (int) $input->getOption('limit');
        $watches = $this->watchRepository->findDueForCheck($limit);

        if (empty($watches)) {
            $io->info('No watches due for checking');
            return Command::SUCCESS;
        }

        $io->info(sprintf('Found %d watches to check', count($watches)));

        $success = 0;
        $failed = 0;

        foreach ($watches as $watch) {
            $io->text("Checking: {$watch->getUrl()}");
            
            $result = $this->priceCheckService->check($watch);
            
            if ($result->wasSuccessful()) {
                $success++;
                $io->text("  ✓ Price: {$result->getPrice()}");
            } else {
                $failed++;
                $io->text("  ✗ Error: {$result->getErrorMessage()}");
            }
        }

        $io->success("Done. Success: $success, Failed: $failed");

        return Command::SUCCESS;
    }
}
