<?php

namespace App\Service;

/**
 * Configuration for category auto-detection mappings.
 * Contains domain mappings, URL patterns, and keyword mappings.
 */
class CategoryMappingConfig
{
    /**
     * Domain to default category slug mapping.
     * Used when no more specific match is found.
     */
    private const DOMAIN_MAPPINGS = [
        // Electronics retailers
        'coolblue.nl' => 'elektronica',
        'coolblue.be' => 'elektronica',
        'mediamarkt.nl' => 'elektronica',
        'mediamarkt.be' => 'elektronica',
        'bcc.nl' => 'elektronica',
        'expert.nl' => 'elektronica',
        'alternate.nl' => 'computers',
        'azerty.nl' => 'computers',
        'megekko.nl' => 'computers',

        // Fashion
        'zalando.nl' => 'mode',
        'zalando.be' => 'mode',
        'aboutyou.nl' => 'mode',
        'asos.com' => 'mode',
        'hm.com' => 'mode',
        'zara.com' => 'mode',
        'nike.com' => 'mode-schoenen',
        'adidas.nl' => 'mode-schoenen',

        // Home & Garden
        'ikea.com' => 'wonen',
        'fonq.nl' => 'wonen',
        'kwantum.nl' => 'wonen',
        'leenbakker.nl' => 'wonen',
        'jysk.nl' => 'wonen',
        'gamma.nl' => 'tuin',
        'praxis.nl' => 'tuin',
        'hornbach.nl' => 'tuin',
        'karwei.nl' => 'tuin',
        'intratuin.nl' => 'tuin',

        // Beauty
        'bol.com/nl/b/parfum' => 'beauty',
        'douglas.nl' => 'beauty',
        'ici-paris.nl' => 'beauty',

        // Sports
        'decathlon.nl' => 'sport',
        'intersport.nl' => 'sport',
        'perrysport.nl' => 'sport',

        // Toys & Games
        'intertoys.nl' => 'speelgoed',
        'toychamp.nl' => 'speelgoed',
        'gamemania.nl' => 'speelgoed',

        // Pets
        'zooplus.nl' => 'huisdier',
        'pets-place.nl' => 'huisdier',
    ];

    /**
     * URL pattern to category slug mapping per domain.
     * Patterns are regex and checked in order.
     */
    private const URL_PATTERNS = [
        'bol.com' => [
            '#/nl/l/laptops#i' => 'computers-laptops',
            '#/nl/l/computers#i' => 'computers',
            '#/nl/l/tablets#i' => 'elektronica-telefoons',
            '#/nl/l/smartphones#i' => 'elektronica-telefoons',
            '#/nl/l/telefoons#i' => 'elektronica-telefoons',
            '#/nl/l/televisies#i' => 'elektronica-tv-audio',
            '#/nl/l/koptelefoons#i' => 'elektronica-tv-audio',
            '#/nl/l/speakers#i' => 'elektronica-tv-audio',
            '#/nl/l/camera#i' => 'elektronica-camera',
            '#/nl/l/kleding#i' => 'mode',
            '#/nl/l/schoenen#i' => 'mode-schoenen',
            '#/nl/l/wonen#i' => 'wonen',
            '#/nl/l/meubels#i' => 'wonen-meubels',
            '#/nl/l/verlichting#i' => 'wonen-verlichting',
            '#/nl/l/keuken#i' => 'wonen-keuken',
            '#/nl/l/tuin#i' => 'tuin',
            '#/nl/l/sport#i' => 'sport',
            '#/nl/l/fietsen#i' => 'sport',
            '#/nl/l/speelgoed#i' => 'speelgoed',
            '#/nl/l/games#i' => 'speelgoed',
            '#/nl/l/beauty#i' => 'beauty',
            '#/nl/l/parfum#i' => 'beauty',
            '#/nl/l/dieren#i' => 'huisdier',
        ],
        'amazon.nl' => [
            '#/dp/.*(?:laptop|notebook)#i' => 'computers-laptops',
            '#/dp/.*computer#i' => 'computers',
            '#/dp/.*(?:iphone|samsung|smartphone)#i' => 'elektronica-telefoons',
            '#/dp/.*(?:tv|televisie)#i' => 'elektronica-tv-audio',
            '#/electronics/#i' => 'elektronica',
            '#/fashion/#i' => 'mode',
            '#/home-garden/#i' => 'wonen',
        ],
        'coolblue.nl' => [
            '#/laptops/#i' => 'computers-laptops',
            '#/computers/#i' => 'computers',
            '#/monitoren/#i' => 'computers-monitoren',
            '#/smartphones/#i' => 'elektronica-telefoons',
            '#/tablets/#i' => 'elektronica-telefoons',
            '#/televisies/#i' => 'elektronica-tv-audio',
            '#/koptelefoons/#i' => 'elektronica-tv-audio',
            '#/speakers/#i' => 'elektronica-tv-audio',
            '#/camera/#i' => 'elektronica-camera',
        ],
        'mediamarkt.nl' => [
            '#/laptops-notebooks#i' => 'computers-laptops',
            '#/computer#i' => 'computers',
            '#/smartphone#i' => 'elektronica-telefoons',
            '#/tablet#i' => 'elektronica-telefoons',
            '#/televisies#i' => 'elektronica-tv-audio',
            '#/koptelefoon#i' => 'elektronica-tv-audio',
            '#/camera#i' => 'elektronica-camera',
        ],
    ];

    /**
     * Keyword to category slug mapping.
     * Checked against normalized (lowercased) product name.
     * Order matters - first match wins.
     */
    private const KEYWORD_MAPPINGS = [
        // Specific laptop products first
        'macbook' => 'computers-laptops',
        'chromebook' => 'computers-laptops',
        'thinkpad' => 'computers-laptops',
        'ideapad' => 'computers-laptops',
        'proart' => 'computers-laptops',  // ASUS ProArt laptops
        'zenbook' => 'computers-laptops', // ASUS ZenBook
        'vivobook' => 'computers-laptops', // ASUS VivoBook
        'rog strix' => 'computers-laptops', // ASUS ROG gaming laptops
        'tuf gaming' => 'computers-laptops', // ASUS TUF gaming laptops
        'surface laptop' => 'computers-laptops',
        'surface pro' => 'computers-laptops',
        'xps' => 'computers-laptops', // Dell XPS
        'latitude' => 'computers-laptops', // Dell Latitude
        'elitebook' => 'computers-laptops', // HP EliteBook
        'spectre' => 'computers-laptops', // HP Spectre
        'pavilion' => 'computers-laptops', // HP Pavilion
        'laptop' => 'computers-laptops',
        'notebook' => 'computers-laptops',

        'imac' => 'computers',
        'desktop' => 'computers',
        'gaming pc' => 'computers',
        'mini pc' => 'computers',

        'monitor' => 'computers-monitoren',
        'beeldscherm' => 'computers-monitoren',

        'iphone' => 'elektronica-telefoons',
        'samsung galaxy' => 'elektronica-telefoons',
        'pixel' => 'elektronica-telefoons',
        'smartphone' => 'elektronica-telefoons',
        'telefoon' => 'elektronica-telefoons',
        'ipad' => 'elektronica-telefoons',
        'tablet' => 'elektronica-telefoons',

        'televisie' => 'elektronica-tv-audio',
        'oled tv' => 'elektronica-tv-audio',
        'qled tv' => 'elektronica-tv-audio',
        ' tv ' => 'elektronica-tv-audio',
        'soundbar' => 'elektronica-tv-audio',
        'koptelefoon' => 'elektronica-tv-audio',
        'headphone' => 'elektronica-tv-audio',
        'airpods' => 'elektronica-tv-audio',
        'speaker' => 'elektronica-tv-audio',
        'bluetooth speaker' => 'elektronica-tv-audio',

        'camera' => 'elektronica-camera',
        'lens' => 'elektronica-camera',
        'gopro' => 'elektronica-camera',

        // Home
        'bank' => 'wonen-meubels',
        'sofa' => 'wonen-meubels',
        'stoel' => 'wonen-meubels',
        'tafel' => 'wonen-meubels',
        'bureau' => 'wonen-meubels',
        'kast' => 'wonen-meubels',
        'bed' => 'wonen-meubels',
        'matras' => 'wonen-meubels',

        'lamp' => 'wonen-verlichting',
        'verlichting' => 'wonen-verlichting',

        'koelkast' => 'wonen-keuken',
        'vaatwasser' => 'wonen-keuken',
        'oven' => 'wonen-keuken',
        'magnetron' => 'wonen-keuken',
        'koffiezetapparaat' => 'wonen-keuken',
        'espressomachine' => 'wonen-keuken',
        'airfryer' => 'wonen-keuken',

        // Fashion
        'sneaker' => 'mode-schoenen',
        'schoen' => 'mode-schoenen',
        'boots' => 'mode-schoenen',
        'sandaal' => 'mode-schoenen',

        'jas' => 'mode-kleding',
        'jack' => 'mode-kleding',
        'jurk' => 'mode-kleding',
        'broek' => 'mode-kleding',
        'jeans' => 'mode-kleding',
        't-shirt' => 'mode-kleding',
        'shirt' => 'mode-kleding',
        'hoodie' => 'mode-kleding',
        'trui' => 'mode-kleding',

        'horloge' => 'mode-accessoires',
        'zonnebril' => 'mode-accessoires',
        'tas' => 'mode-accessoires',

        // Garden
        'grasmaaier' => 'tuin',
        'tuinmeubel' => 'tuin',
        'parasol' => 'tuin',
        'barbecue' => 'tuin',
        'bbq' => 'tuin',

        // Sports
        'fiets' => 'sport',
        'e-bike' => 'sport',
        'hometrainer' => 'sport',
        'loopband' => 'sport',
        'fitness' => 'sport',

        // Gaming & Toys
        'playstation' => 'speelgoed',
        'xbox' => 'speelgoed',
        'nintendo' => 'speelgoed',
        'controller' => 'speelgoed',
        'lego' => 'speelgoed',

        // Beauty
        'parfum' => 'beauty',
        'gezichtscr' => 'beauty',
        'shampoo' => 'beauty',
        'make-up' => 'beauty',

        // Pets
        'hondenvoer' => 'huisdier',
        'kattenvoer' => 'huisdier',
        'kattenbak' => 'huisdier',
        'hondenmand' => 'huisdier',
    ];

    /**
     * Get category slug for a domain, or null if no mapping.
     */
    public function getDomainCategorySlug(string $domain): ?string
    {
        // Remove www. prefix
        $domain = preg_replace('/^www\./i', '', $domain);

        return self::DOMAIN_MAPPINGS[$domain] ?? null;
    }

    /**
     * Get URL patterns for a domain.
     * @return array<string, string> Pattern => category slug
     */
    public function getUrlPatternsForDomain(string $domain): array
    {
        // Remove www. prefix
        $domain = preg_replace('/^www\./i', '', $domain);

        return self::URL_PATTERNS[$domain] ?? [];
    }

    /**
     * Get all keyword mappings.
     * @return array<string, string> Keyword => category slug
     */
    public function getKeywordMappings(): array
    {
        return self::KEYWORD_MAPPINGS;
    }

    /**
     * Match URL against patterns for a domain.
     * Returns category slug or null.
     */
    public function matchUrlPattern(string $url, string $domain): ?string
    {
        $patterns = $this->getUrlPatternsForDomain($domain);

        foreach ($patterns as $pattern => $categorySlug) {
            if (preg_match($pattern, $url)) {
                return $categorySlug;
            }
        }

        return null;
    }

    /**
     * Match product name against keywords.
     * Returns category slug or null.
     */
    public function matchKeywords(string $productName): ?string
    {
        $normalizedName = mb_strtolower(trim($productName));

        foreach (self::KEYWORD_MAPPINGS as $keyword => $categorySlug) {
            if (str_contains($normalizedName, $keyword)) {
                return $categorySlug;
            }
        }

        return null;
    }
}
