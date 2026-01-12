<?php

namespace App\Repository;

use App\Entity\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Category>
 */
class CategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Category::class);
    }

    /**
     * Find all root categories (no parent) with their children.
     * @return Category[]
     */
    public function findRootCategories(): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.parent IS NULL')
            ->orderBy('c.sortOrder', 'ASC')
            ->addOrderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find a category by slug.
     */
    public function findBySlug(string $slug): ?Category
    {
        return $this->findOneBy(['slug' => $slug]);
    }

    /**
     * Find category by normalized name (case-insensitive).
     */
    public function findByNormalizedName(string $name): ?Category
    {
        return $this->createQueryBuilder('c')
            ->where('LOWER(c.name) = LOWER(:name)')
            ->setParameter('name', $name)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Get category tree with product counts.
     * @return array<array{id: int, name: string, slug: string, icon: ?string, productCount: int, children: array}>
     */
    public function getCategoryTreeWithCounts(): array
    {
        // Get all categories with product counts
        $qb = $this->createQueryBuilder('c')
            ->select('c.id, c.name, c.slug, c.icon, c.sortOrder, IDENTITY(c.parent) as parentId')
            ->addSelect('(SELECT COUNT(pw.id) FROM App\Entity\ProductWatch pw WHERE pw.category = c AND pw.isPublic = true AND pw.isActive = true AND pw.currentPrice IS NOT NULL) as productCount')
            ->orderBy('c.sortOrder', 'ASC')
            ->addOrderBy('c.name', 'ASC');

        $results = $qb->getQuery()->getResult();

        // Build tree structure
        $byId = [];
        $roots = [];

        foreach ($results as $row) {
            $byId[$row['id']] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'slug' => $row['slug'],
                'icon' => $row['icon'],
                'productCount' => (int) $row['productCount'],
                'children' => [],
            ];
        }

        foreach ($results as $row) {
            if ($row['parentId'] === null) {
                $roots[] = &$byId[$row['id']];
            } else {
                $byId[$row['parentId']]['children'][] = &$byId[$row['id']];
            }
        }

        // Add child counts to parent counts
        foreach ($roots as &$root) {
            $this->aggregateChildCounts($root);
        }

        return $roots;
    }

    /**
     * Recursively aggregate child product counts to parents.
     */
    private function aggregateChildCounts(array &$category): int
    {
        $total = $category['productCount'];

        foreach ($category['children'] as &$child) {
            $total += $this->aggregateChildCounts($child);
        }

        $category['productCount'] = $total;
        return $total;
    }

    /**
     * Get all category IDs for a category including its descendants.
     * @return int[]
     */
    public function getDescendantIds(int $categoryId): array
    {
        $category = $this->find($categoryId);
        if (!$category) {
            return [];
        }

        return $category->getDescendantIds();
    }
}
