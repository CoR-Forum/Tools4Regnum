<?php
namespace App\models;

/**
 * Read-only model for file-based resources (textures, sounds, music)
 * loaded from data/files.json. Not stored in SQLite.
 */
class FileResource
{
    /** Cached parsed data */
    private static ?array $data = null;

    /** Category slug → JSON key mapping */
    private const CATEGORY_MAP = [
        'textures' => 'textures',
        'sounds'   => 'sounds',
        'music'    => 'music',
    ];

    /** Base URL for file downloads */
    private const BASE_URL = 'https://cor-forum.de/regnum/datengrab/res/';

    /** Category slug → subdirectory in the URL */
    private const URL_DIR = [
        'textures' => 'TEXTURE',
        'sounds'   => 'SOUND',
        'music'    => 'MUSIC',
    ];

    /**
     * Check if a category slug is a file-based resource category.
     */
    public static function isFileCategory(string $slug): bool
    {
        return isset(self::CATEGORY_MAP[$slug]);
    }

    /**
     * Get all file-based category slugs.
     */
    public static function fileCategorySlugs(): array
    {
        return array_keys(self::CATEGORY_MAP);
    }

    /**
     * Load and cache the files.json data.
     */
    private static function loadData(): array
    {
        if (self::$data === null) {
            $path = DATA_PATH . '/files.json';
            if (!file_exists($path)) {
                self::$data = ['textures' => [], 'sounds' => [], 'music' => []];
            } else {
                $raw = json_decode(file_get_contents($path), true);
                self::$data = $raw ?: ['textures' => [], 'sounds' => [], 'music' => []];
            }
        }
        return self::$data;
    }

    /**
     * Get the last update timestamp from files.json.
     */
    public static function lastUpdate(): ?string
    {
        $data = self::loadData();
        return $data['lastUpdate'] ?? null;
    }

    /**
     * Get all items for a file category.
     *
     * @return array Each item has: filename, name, id (numeric prefix), url, slug
     */
    public static function all(string $categorySlug): array
    {
        $data = self::loadData();
        $key = self::CATEGORY_MAP[$categorySlug] ?? null;
        if (!$key || empty($data[$key])) {
            return [];
        }

        $dir = self::URL_DIR[$categorySlug] ?? 'TEXTURE';
        $items = [];
        foreach ($data[$key] as $entry) {
            $items[] = self::enrichItem($entry, $categorySlug, $dir);
        }
        return $items;
    }

    /**
     * Count items in a file category.
     */
    public static function count(string $categorySlug): int
    {
        $data = self::loadData();
        $key = self::CATEGORY_MAP[$categorySlug] ?? null;
        if (!$key) return 0;
        return count($data[$key] ?? []);
    }

    /**
     * Search items by query (case-insensitive substring match on filename).
     */
    public static function search(string $categorySlug, string $query): array
    {
        $all = self::all($categorySlug);
        if ($query === '') return $all;

        $q = mb_strtolower($query);
        return array_values(array_filter($all, function ($item) use ($q) {
            return mb_strpos(mb_strtolower($item['name']), $q) !== false;
        }));
    }

    /**
     * Paginate items.
     *
     * @return array{items: array, pagination: array}
     */
    public static function paginate(string $categorySlug, int $page = 1, int $perPage = 48, string $query = ''): array
    {
        $items = ($query !== '') ? self::search($categorySlug, $query) : self::all($categorySlug);
        $total = count($items);
        $totalPages = max(1, (int)ceil($total / $perPage));
        $page = max(1, min($page, $totalPages));
        $offset = ($page - 1) * $perPage;

        return [
            'items' => array_slice($items, $offset, $perPage),
            'pagination' => [
                'page'       => $page,
                'totalPages' => $totalPages,
                'total'      => $total,
                'offset'     => $offset,
                'limit'      => $perPage,
            ],
        ];
    }

    /**
     * Find a single item by its slug.
     */
    public static function findBySlug(string $categorySlug, string $slug): ?array
    {
        $all = self::all($categorySlug);
        foreach ($all as $item) {
            if ($item['slug'] === $slug) {
                return $item;
            }
        }
        return null;
    }

    /**
     * Build the full download/preview URL for a filename.
     */
    public static function buildUrl(string $categorySlug, string $filename): string
    {
        $dir = self::URL_DIR[$categorySlug] ?? 'TEXTURE';
        return self::BASE_URL . $dir . '/' . rawurlencode($filename);
    }

    /**
     * Extract a display name from a filename like "31359-Aura.png" → "Aura".
     */
    private static function extractName(string $filename): string
    {
        // Remove extension
        $name = pathinfo($filename, PATHINFO_FILENAME);
        // Remove numeric ID prefix: "31359-Aura" → "Aura"
        if (preg_match('/^\d+-(.+)$/', $name, $m)) {
            return $m[1];
        }
        return $name;
    }

    /**
     * Extract the numeric ID prefix from a filename.
     */
    private static function extractId(string $filename): int
    {
        if (preg_match('/^(\d+)-/', $filename, $m)) {
            return (int)$m[1];
        }
        return 0;
    }

    /**
     * Create a slug from a filename.
     */
    private static function makeSlug(string $filename): string
    {
        $name = pathinfo($filename, PATHINFO_FILENAME);
        $slug = mb_strtolower($name);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        return trim($slug, '-');
    }

    /**
     * Enrich a raw item from the JSON with computed fields.
     */
    private static function enrichItem(array $entry, string $categorySlug, string $dir): array
    {
        $filename = $entry['filename'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        return [
            'filename'  => $filename,
            'name'      => self::extractName($filename),
            'file_id'   => self::extractId($filename),
            'slug'      => self::makeSlug($filename),
            'url'       => self::BASE_URL . $dir . '/' . rawurlencode($filename),
            'extension' => $ext,
            'type'      => self::fileType($ext),
            'category'  => $categorySlug,
        ];
    }

    /**
     * Determine file type from extension.
     */
    private static function fileType(string $ext): string
    {
        return match ($ext) {
            'png', 'jpg', 'jpeg', 'gif', 'webp', 'bmp', 'tga', 'dds' => 'image',
            'ogg', 'mp3', 'wav', 'flac' => 'audio',
            default => 'other',
        };
    }
}
