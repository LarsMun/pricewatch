<?php

namespace App\Service;

use App\Entity\Category;
use App\Repository\CategoryRepository;

/**
 * Service for auto-categorizing products based on various signals.
 */
class CategoryService
{
    public function __construct(
        private CategoryRepository $categoryRepository,
        private CategoryMappingConfig $mappingConfig,
    ) {}

    /**
     * Determine the best category for a product using priority-based strategy.
     *
     * Priority:
     * 1. JSON-LD category field (most reliable - from structured data)
     * 2. URL pattern matching (path-based hints - e.g. /laptops/)
     * 3. Product name keywords (specific product identification)
     * 4. Domain mapping (retailer specialization - fallback for generic retailers)
     * 5. "Overig" fallback
     *
     * @param string $url The product URL
     * @param string $domain The domain (extracted from URL)
     * @param string|null $productName The product name if available
     * @param string|null $jsonLdCategory Category from JSON-LD structured data
     */
    public function determineCategory(
        string $url,
        string $domain,
        ?string $productName,
        ?string $jsonLdCategory
    ): ?Category {
        // 1. Try JSON-LD category (best source)
        if ($jsonLdCategory !== null && $jsonLdCategory !== '') {
            $category = $this->matchJsonLdCategory($jsonLdCategory);
            if ($category !== null) {
                return $category;
            }
        }

        // 2. Try URL pattern matching (specific paths like /laptops/)
        $categorySlug = $this->mappingConfig->matchUrlPattern($url, $domain);
        if ($categorySlug !== null) {
            $category = $this->categoryRepository->findBySlug($categorySlug);
            if ($category !== null) {
                return $category;
            }
        }

        // 3. Try product name keywords (more specific than domain)
        if ($productName !== null && $productName !== '') {
            $categorySlug = $this->mappingConfig->matchKeywords($productName);
            if ($categorySlug !== null) {
                $category = $this->categoryRepository->findBySlug($categorySlug);
                if ($category !== null) {
                    return $category;
                }
            }
        }

        // 4. Try domain mapping (general fallback for retailers)
        $categorySlug = $this->mappingConfig->getDomainCategorySlug($domain);
        if ($categorySlug !== null) {
            $category = $this->categoryRepository->findBySlug($categorySlug);
            if ($category !== null) {
                return $category;
            }
        }

        // 5. Fallback to "Overig"
        return $this->categoryRepository->findBySlug('overig');
    }

    /**
     * Match a JSON-LD category string to our category taxonomy.
     *
     * JSON-LD categories often use hierarchical formats:
     * - "Electronics > Computers > Laptops"
     * - "Electronics/Computers/Laptops"
     * - "Elektronica > Computers > Laptops"
     * - Just "Laptops"
     */
    private function matchJsonLdCategory(string $jsonLdCategory): ?Category
    {
        // Split by common hierarchy separators
        $parts = preg_split('/\s*[>\/|]\s*/', $jsonLdCategory);
        if ($parts === false) {
            $parts = [$jsonLdCategory];
        }

        // Clean up parts
        $parts = array_map('trim', $parts);
        $parts = array_filter($parts, fn($p) => $p !== '');

        if (empty($parts)) {
            return null;
        }

        // Try to match from deepest (most specific) to shallowest
        for ($i = count($parts) - 1; $i >= 0; $i--) {
            $categoryName = $parts[$i];

            // Try exact match on name
            $category = $this->categoryRepository->findByNormalizedName($categoryName);
            if ($category !== null) {
                return $category;
            }

            // Try common translations/synonyms
            $translated = $this->translateCategoryName($categoryName);
            if ($translated !== null) {
                $category = $this->categoryRepository->findBySlug($translated);
                if ($category !== null) {
                    return $category;
                }
            }
        }

        return null;
    }

    /**
     * Translate common English category names to our Dutch slugs.
     */
    private function translateCategoryName(string $name): ?string
    {
        $translations = [
            // Electronics
            'electronics' => 'elektronica',
            'computers' => 'computers',
            'laptops' => 'computers-laptops',
            'notebooks' => 'computers-laptops',
            'monitors' => 'computers-monitoren',
            'displays' => 'computers-monitoren',
            'components' => 'computers-componenten',
            'computer components' => 'computers-componenten',
            'phones' => 'elektronica-telefoons',
            'smartphones' => 'elektronica-telefoons',
            'mobile phones' => 'elektronica-telefoons',
            'tablets' => 'elektronica-telefoons',
            'televisions' => 'elektronica-tv-audio',
            'tvs' => 'elektronica-tv-audio',
            'audio' => 'elektronica-tv-audio',
            'headphones' => 'elektronica-tv-audio',
            'speakers' => 'elektronica-tv-audio',
            'cameras' => 'elektronica-camera',
            'photo' => 'elektronica-camera',
            'video' => 'elektronica-camera',

            // Home
            'home' => 'wonen',
            'home & garden' => 'wonen',
            'furniture' => 'wonen-meubels',
            'lighting' => 'wonen-verlichting',
            'kitchen' => 'wonen-keuken',
            'appliances' => 'wonen-keuken',

            // Fashion
            'fashion' => 'mode',
            'clothing' => 'mode-kleding',
            'apparel' => 'mode-kleding',
            'shoes' => 'mode-schoenen',
            'footwear' => 'mode-schoenen',
            'accessories' => 'mode-accessoires',
            'jewelry' => 'mode-accessoires',
            'watches' => 'mode-accessoires',

            // Others
            'garden' => 'tuin',
            'outdoor' => 'tuin',
            'sports' => 'sport',
            'fitness' => 'sport',
            'toys' => 'speelgoed',
            'games' => 'speelgoed',
            'gaming' => 'speelgoed',
            'beauty' => 'beauty',
            'health' => 'beauty',
            'cosmetics' => 'beauty',
            'pets' => 'huisdier',
            'pet supplies' => 'huisdier',
        ];

        $normalized = mb_strtolower(trim($name));
        return $translations[$normalized] ?? null;
    }
}
