<?php

namespace App\Command;

use App\Scraper\HttpEngine;
use App\Scraper\PriceExtractor;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-scrape',
    description: 'Test scraping a URL with a CSS selector',
)]
class TestScrapeCommand extends Command
{
    public function __construct(
        private HttpEngine $httpEngine,
        private PriceExtractor $priceExtractor,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('url', InputArgument::REQUIRED, 'URL to scrape')
            ->addArgument('selector', InputArgument::REQUIRED, 'CSS selector for the price element')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $url = $input->getArgument('url');
        $selector = $input->getArgument('selector');

        $io->title('Test Scrape');
        $io->text("URL: $url");
        $io->text("Selector: $selector");
        $io->newLine();

        // Fetch
        $io->section('Fetching page...');
        $scrapeResult = $this->httpEngine->fetch($url);

        if (!$scrapeResult->success) {
            $io->error("Fetch failed: {$scrapeResult->error}");
            return Command::FAILURE;
        }

        $io->text("HTTP Status: {$scrapeResult->httpStatus}");
        $io->text("Duration: {$scrapeResult->durationMs}ms");
        $io->text("HTML length: " . strlen($scrapeResult->html) . " bytes");
        $io->newLine();

        // Extract
        $io->section('Extracting price...');
        $extractResult = $this->priceExtractor->extract($scrapeResult->html, $selector);

        if (!$extractResult->success) {
            $io->error("Extraction failed: {$extractResult->error}");
            
            // Show some context for debugging
            $io->section('HTML snippet (first 2000 chars):');
            $io->text(substr($scrapeResult->html, 0, 2000));
            
            return Command::FAILURE;
        }

        $io->success([
            "Raw text: {$extractResult->rawText}",
            "Parsed price: {$extractResult->price}",
        ]);

        return Command::SUCCESS;
    }
}
