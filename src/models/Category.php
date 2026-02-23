<?php
namespace App\models;

use App\Database;

class Category
{
    /**
     * Get all categories with translations for current language.
     */
    public static function all(?string $lang = null): array
    {
        $lang = $lang ?? currentLang();
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('
            SELECT c.*, ct.name, ct.description
            FROM categories c
            LEFT JOIN category_translations ct ON ct.category_id = c.id AND ct.lang = :lang
            ORDER BY c.sort_order ASC
        ');
        $stmt->execute([':lang' => $lang]);
        $results = $stmt->fetchAll();

        // Fallback to German if translation is missing
        if ($lang !== 'de') {
            foreach ($results as &$row) {
                if (empty($row['name'])) {
                    $fb = $pdo->prepare('SELECT name, description FROM category_translations WHERE category_id = :cid AND lang = \'de\'');
                    $fb->execute([':cid' => $row['id']]);
                    $fallback = $fb->fetch();
                    if ($fallback) {
                        $row['name'] = $fallback['name'];
                        $row['description'] = $fallback['description'];
                    }
                }
            }
        }
        return $results;
    }

    /**
     * Find a category by slug.
     */
    public static function findBySlug(string $slug, ?string $lang = null): ?array
    {
        $lang = $lang ?? currentLang();
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('
            SELECT c.*, ct.name, ct.description
            FROM categories c
            LEFT JOIN category_translations ct ON ct.category_id = c.id AND ct.lang = :lang
            WHERE c.slug = :slug
        ');
        $stmt->execute([':slug' => $slug, ':lang' => $lang]);
        $row = $stmt->fetch();

        if (!$row) return null;

        // Fallback
        if (empty($row['name']) && $lang !== 'de') {
            $fb = $pdo->prepare('SELECT name, description FROM category_translations WHERE category_id = :cid AND lang = \'de\'');
            $fb->execute([':cid' => $row['id']]);
            $fallback = $fb->fetch();
            if ($fallback) {
                $row['name'] = $fallback['name'];
                $row['description'] = $fallback['description'];
            }
        }

        return $row ?: null;
    }

    /**
     * Find a category by ID.
     */
    public static function find(int $id): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM categories WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Count entries in a category.
     */
    public static function entryCount(int $categoryId): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT COUNT(*) as cnt FROM entries WHERE category_id = :cid');
        $stmt->execute([':cid' => $categoryId]);
        return (int)$stmt->fetch()['cnt'];
    }
}
