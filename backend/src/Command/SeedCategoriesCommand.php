<?php

namespace App\Command;

use App\Entity\Category;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:seed-categories',
    description: 'Seed the category taxonomy',
)]
class SeedCategoriesCommand extends Command
{
    /**
     * Dutch category taxonomy with icons.
     * Structure: [name, slug, icon, children[]]
     */
    private const TAXONOMY = [
        [
            'name' => 'Elektronica',
            'slug' => 'elektronica',
            'icon' => '💻',
            'children' => [
                ['name' => 'Telefoons & Tablets', 'slug' => 'elektronica-telefoons', 'icon' => '📱'],
                ['name' => 'TV & Audio', 'slug' => 'elektronica-tv-audio', 'icon' => '📺'],
                ['name' => 'Foto & Video', 'slug' => 'elektronica-camera', 'icon' => '📷'],
            ],
        ],
        [
            'name' => 'Computers',
            'slug' => 'computers',
            'icon' => '🖥️',
            'children' => [
                ['name' => 'Laptops', 'slug' => 'computers-laptops', 'icon' => '💻'],
                ['name' => 'Monitoren', 'slug' => 'computers-monitoren', 'icon' => '🖥️'],
                ['name' => 'Componenten', 'slug' => 'computers-componenten', 'icon' => '🔧'],
            ],
        ],
        [
            'name' => 'Wonen',
            'slug' => 'wonen',
            'icon' => '🏠',
            'children' => [
                ['name' => 'Meubels', 'slug' => 'wonen-meubels', 'icon' => '🛋️'],
                ['name' => 'Verlichting', 'slug' => 'wonen-verlichting', 'icon' => '💡'],
                ['name' => 'Keuken', 'slug' => 'wonen-keuken', 'icon' => '🍳'],
            ],
        ],
        [
            'name' => 'Mode',
            'slug' => 'mode',
            'icon' => '👕',
            'children' => [
                ['name' => 'Kleding', 'slug' => 'mode-kleding', 'icon' => '👔'],
                ['name' => 'Schoenen', 'slug' => 'mode-schoenen', 'icon' => '👟'],
                ['name' => 'Accessoires', 'slug' => 'mode-accessoires', 'icon' => '⌚'],
            ],
        ],
        [
            'name' => 'Tuin & Klussen',
            'slug' => 'tuin',
            'icon' => '🌳',
            'children' => [],
        ],
        [
            'name' => 'Sport & Vrije tijd',
            'slug' => 'sport',
            'icon' => '🚴',
            'children' => [],
        ],
        [
            'name' => 'Speelgoed & Games',
            'slug' => 'speelgoed',
            'icon' => '🎮',
            'children' => [],
        ],
        [
            'name' => 'Beauty & Gezondheid',
            'slug' => 'beauty',
            'icon' => '💄',
            'children' => [],
        ],
        [
            'name' => 'Huisdieren',
            'slug' => 'huisdier',
            'icon' => '🐾',
            'children' => [],
        ],
        [
            'name' => 'Overig',
            'slug' => 'overig',
            'icon' => '🏷️',
            'children' => [],
        ],
    ];

    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force re-seeding (delete existing categories first)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Check if categories already exist
        $existingCount = $this->entityManager
            ->getRepository(Category::class)
            ->count([]);

        if ($existingCount > 0) {
            if (!$input->getOption('force')) {
                $io->warning(sprintf(
                    'Categories already exist (%d found). Use --force to re-seed.',
                    $existingCount
                ));
                return Command::SUCCESS;
            }

            $io->note('Deleting existing categories...');
            $this->entityManager->createQuery('DELETE FROM App\Entity\Category')->execute();
        }

        $io->section('Seeding categories...');

        $count = 0;
        foreach (self::TAXONOMY as $sortOrder => $data) {
            $parent = $this->createCategory($data, null, $sortOrder);
            $this->entityManager->persist($parent);
            $count++;
            $io->text(sprintf('  %s %s', $data['icon'], $data['name']));

            foreach ($data['children'] ?? [] as $childSortOrder => $childData) {
                $child = $this->createCategory($childData, $parent, $childSortOrder);
                $this->entityManager->persist($child);
                $count++;
                $io->text(sprintf('    └─ %s %s', $childData['icon'], $childData['name']));
            }
        }

        $this->entityManager->flush();

        $io->success(sprintf('Created %d categories.', $count));

        return Command::SUCCESS;
    }

    private function createCategory(array $data, ?Category $parent, int $sortOrder): Category
    {
        $category = new Category();
        $category->setName($data['name']);
        $category->setSlug($data['slug']);
        $category->setIcon($data['icon'] ?? null);
        $category->setSortOrder($sortOrder);
        $category->setParent($parent);

        return $category;
    }
}
