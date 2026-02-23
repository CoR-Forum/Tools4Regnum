<?php
namespace App\models;

use App\Database;

class Favorite
{
    /**
     * Check if an entry is favorited by a user.
     */
    public static function isFavorited(int $userId, int $entryId): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT 1 FROM favorites WHERE user_id = :uid AND entry_id = :eid');
        $stmt->execute([':uid' => $userId, ':eid' => $entryId]);
        return (bool)$stmt->fetch();
    }

    /**
     * Toggle favorite. Returns true if now favorited, false if removed.
     */
    public static function toggle(int $userId, int $entryId): bool
    {
        $pdo = Database::getConnection();

        if (self::isFavorited($userId, $entryId)) {
            $stmt = $pdo->prepare('DELETE FROM favorites WHERE user_id = :uid AND entry_id = :eid');
            $stmt->execute([':uid' => $userId, ':eid' => $entryId]);
            return false;
        }

        $stmt = $pdo->prepare('INSERT INTO favorites (user_id, entry_id) VALUES (:uid, :eid)');
        $stmt->execute([':uid' => $userId, ':eid' => $entryId]);
        return true;
    }

    /**
     * Get all favorited entries for a user.
     */
    public static function forUser(int $userId, ?string $lang = null): array
    {
        $lang = $lang ?? currentLang();
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('
            SELECT e.*, et.title, et.summary, e.images_json,
                   c.slug as category_slug, ct.name as category_name,
                   f.created_at as favorited_at
            FROM favorites f
            JOIN entries e ON e.id = f.entry_id
            LEFT JOIN entry_translations et ON et.entry_id = e.id AND et.lang = :lang
            LEFT JOIN categories c ON c.id = e.category_id
            LEFT JOIN category_translations ct ON ct.category_id = c.id AND ct.lang = :lang2
            WHERE f.user_id = :uid
            ORDER BY f.created_at DESC
        ');
        $stmt->execute([':uid' => $userId, ':lang' => $lang, ':lang2' => $lang]);
        $rows = $stmt->fetchAll();

        // Fallback translations
        if ($lang !== 'de') {
            foreach ($rows as &$row) {
                if (empty($row['title'])) {
                    $fb = $pdo->prepare('SELECT title, summary FROM entry_translations WHERE entry_id = :eid AND lang = \'de\'');
                    $fb->execute([':eid' => $row['id']]);
                    $fallback = $fb->fetch();
                    if ($fallback) {
                        $row['title'] = $fallback['title'];
                        $row['summary'] = $fallback['summary'];
                    }
                }
                if (empty($row['category_name'])) {
                    $fb = $pdo->prepare('SELECT name FROM category_translations WHERE category_id = :cid AND lang = \'de\'');
                    $fb->execute([':cid' => $row['category_id']]);
                    $fallback = $fb->fetch();
                    if ($fallback) {
                        $row['category_name'] = $fallback['name'];
                    }
                }
            }
        }

        return $rows;
    }

    /**
     * Count favorites for a user.
     */
    public static function countForUser(int $userId): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT COUNT(*) as cnt FROM favorites WHERE user_id = :uid');
        $stmt->execute([':uid' => $userId]);
        return (int)$stmt->fetch()['cnt'];
    }

    /**
     * Get favorite entry IDs for a user (for batch checking).
     */
    public static function entryIdsForUser(int $userId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT entry_id FROM favorites WHERE user_id = :uid');
        $stmt->execute([':uid' => $userId]);
        return array_column($stmt->fetchAll(), 'entry_id');
    }
}
