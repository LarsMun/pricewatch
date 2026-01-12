<?php

namespace App\Command;

use App\Entity\ProductWatch;
use App\Service\CategoryService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:backfill-categories',
    description: 'Categorize existing products that have no category',
)]
class BackfillCategoriesCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CategoryService $categoryService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Re-categorize all products, not just those without a category')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Show what would be done without making changes');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $force = $input->getOption('force');
        $dryRun = $input->getOption('dry-run');

        // Get products to categorize
        $qb = $this->entityManager
            ->getRepository(ProductWatch::class)
            ->createQueryBuilder('pw');

        if (!$force) {
            $qb->where('pw.category IS NULL');
        }

        $watches = $qb->getQuery()->getResult();
        $count = count($watches);

        if ($count === 0) {
            $io->success('No products to categorize.');
            return Command::SUCCESS;
        }

        $io->section(sprintf('Categorizing %d products...', $count));

        if ($dryRun) {
            $io->note('Dry run mode - no changes will be made.');
        }

        $progress = new ProgressBar($output, $count);
        $progress->setFormat(' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s%');
        $progress->start();

        $categoryCounts = [];
        $batchSize = 50;
        $i = 0;

        foreach ($watches as $watch) {
            /** @var ProductWatch $watch */
            $category = $this->categoryService->determineCategory(
                $watch->getUrl(),
                $watch->getDomain(),
                $watch->getProductName(),
                null // No JSON-LD data for existing products
            );

            if ($category !== null) {
                $categoryName = $category->getName();
                $categoryCounts[$categoryName] = ($categoryCounts[$categoryName] ?? 0) + 1;

                if (!$dryRun) {
                    $watch->setCategory($category);
                }
            }

            $progress->advance();
            $i++;

            // Flush in batches
            if ($i % $batchSize === 0 && !$dryRun) {
                $this->entityManager->flush();
            }
        }

        // Final flush
        if (!$dryRun) {
            $this->entityManager->flush();
        }

        $progress->finish();
        $output->writeln('');
        $output->writeln('');

        // Show summary
        $io->section('Category distribution:');
        arsort($categoryCounts);
        foreach ($categoryCounts as $name => $count) {
            $io->text(sprintf('  %s: %d', $name, $count));
        }

        if ($dryRun) {
            $io->warning('Dry run completed. No changes were made.');
        } else {
            $io->success(sprintf('Categorized %d products.', count($watches)));
        }

        return Command::SUCCESS;
    }
}
