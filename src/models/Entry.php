<?php
namespace App\models;

use App\Database;

class Entry
{
    /**
     * List entries for a category with translations.
     */
    public static function byCategory(int $categoryId, string $lang = null, int $limit = 24, int $offset = 0): array
    {
        $lang = $lang ?? currentLang();
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('
            SELECT e.*, et.title, et.summary, et.body,
                   c.slug as category_slug
            FROM entries e
            LEFT JOIN entry_translations et ON et.entry_id = e.id AND et.lang = :lang
            LEFT JOIN categories c ON c.id = e.category_id
            WHERE e.category_id = :cid
            ORDER BY e.updated_at DESC
            LIMIT :limit OFFSET :offset
        ');
        $stmt->bindValue(':lang', $lang);
        $stmt->bindValue(':cid', $categoryId, \PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll();

        return self::fillFallbackTranslations($pdo, $rows, $lang);
    }

    /**
     * Count entries in a category.
     */
    public static function countByCategory(int $categoryId): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT COUNT(*) as cnt FROM entries WHERE category_id = :cid');
        $stmt->execute([':cid' => $categoryId]);
        return (int)$stmt->fetch()['cnt'];
    }

    /**
     * Find a single entry by category slug + entry slug.
     */
    public static function findBySlugs(string $categorySlug, string $entrySlug, string $lang = null): ?array
    {
        $lang = $lang ?? currentLang();
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('
            SELECT e.*, et.title, et.summary, et.body,
                   c.slug as category_slug, c.id as category_id
            FROM entries e
            JOIN categories c ON c.id = e.category_id AND c.slug = :catSlug
            LEFT JOIN entry_translations et ON et.entry_id = e.id AND et.lang = :lang
            WHERE e.slug = :slug
        ');
        $stmt->execute([':catSlug' => $categorySlug, ':slug' => $entrySlug, ':lang' => $lang]);
        $row = $stmt->fetch();

        if (!$row) return null;

        // Fallback translation
        if (empty($row['title']) && $lang !== 'de') {
            $fb = $pdo->prepare('SELECT title, summary, body FROM entry_translations WHERE entry_id = :eid AND lang = \'de\'');
            $fb->execute([':eid' => $row['id']]);
            $fallback = $fb->fetch();
            if ($fallback) {
                $row['title'] = $fallback['title'];
                $row['summary'] = $fallback['summary'];
                $row['body'] = $fallback['body'];
            }
        }

        // Decode JSON fields
        $row['data'] = json_decode($row['data_json'] ?? '{}', true) ?: [];
        $row['images'] = json_decode($row['images_json'] ?? '[]', true) ?: [];

        return $row;
    }

    /**
     * Find entry by ID.
     */
    public static function find(int $id, string $lang = null): ?array
    {
        $lang = $lang ?? currentLang();
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('
            SELECT e.*, et.title, et.summary, et.body,
                   c.slug as category_slug
            FROM entries e
            LEFT JOIN entry_translations et ON et.entry_id = e.id AND et.lang = :lang
            LEFT JOIN categories c ON c.id = e.category_id
            WHERE e.id = :id
        ');
        $stmt->execute([':id' => $id, ':lang' => $lang]);
        $row = $stmt->fetch();
        if (!$row) return null;

        $row['data'] = json_decode($row['data_json'] ?? '{}', true) ?: [];
        $row['images'] = json_decode($row['images_json'] ?? '[]', true) ?: [];
        return $row;
    }

    /**
     * Get all translations for an entry.
     */
    public static function getTranslations(int $entryId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM entry_translations WHERE entry_id = :eid');
        $stmt->execute([':eid' => $entryId]);
        $rows = $stmt->fetchAll();
        $result = [];
        foreach ($rows as $row) {
            $result[$row['lang']] = $row;
        }
        return $result;
    }

    /**
     * Create a new entry with translations.
     */
    public static function create(array $data, array $translations): int
    {
        $pdo = Database::getConnection();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare('
                INSERT INTO entries (category_id, slug, images_json, data_json, created_by_userid)
                VALUES (:cid, :slug, :images, :data, :uid)
            ');
            $stmt->execute([
                ':cid'    => $data['category_id'],
                ':slug'   => $data['slug'],
                ':images' => json_encode($data['images'] ?? []),
                ':data'   => json_encode($data['data'] ?? []),
                ':uid'    => $data['created_by_userid'] ?? 0,
            ]);
            $entryId = (int)$pdo->lastInsertId();

            foreach ($translations as $lang => $trans) {
                if (empty($trans['title'])) continue;
                $stmt = $pdo->prepare('
                    INSERT INTO entry_translations (entry_id, lang, title, summary, body)
                    VALUES (:eid, :lang, :title, :summary, :body)
                ');
                $stmt->execute([
                    ':eid'     => $entryId,
                    ':lang'    => $lang,
                    ':title'   => $trans['title'],
                    ':summary' => $trans['summary'] ?? '',
                    ':body'    => $trans['body'] ?? '',
                ]);
            }

            $pdo->commit();
            return $entryId;
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Update an entry and its translations.
     */
    public static function update(int $id, array $data, array $translations): void
    {
        $pdo = Database::getConnection();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare('
                UPDATE entries SET
                    category_id = :cid,
                    slug = :slug,
                    images_json = :images,
                    data_json = :data,
                    updated_at = datetime(\'now\')
                WHERE id = :id
            ');
            $stmt->execute([
                ':id'     => $id,
                ':cid'    => $data['category_id'],
                ':slug'   => $data['slug'],
                ':images' => json_encode($data['images'] ?? []),
                ':data'   => json_encode($data['data'] ?? []),
            ]);

            foreach ($translations as $lang => $trans) {
                if (empty($trans['title'])) continue;
                $stmt = $pdo->prepare('
                    INSERT INTO entry_translations (entry_id, lang, title, summary, body)
                    VALUES (:eid, :lang, :title, :summary, :body)
                    ON CONFLICT(entry_id, lang) DO UPDATE SET
                        title = :title,
                        summary = :summary,
                        body = :body
                ');
                $stmt->execute([
                    ':eid'     => $id,
                    ':lang'    => $lang,
                    ':title'   => $trans['title'],
                    ':summary' => $trans['summary'] ?? '',
                    ':body'    => $trans['body'] ?? '',
                ]);
            }

            $pdo->commit();
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Delete an entry.
     */
    public static function delete(int $id): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('DELETE FROM entries WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

    /**
     * Full-text search across entry translations.
     */
    public static function search(string $query, string $lang = null, int $limit = 50): array
    {
        $lang = $lang ?? currentLang();
        $pdo = Database::getConnection();

        // Use FTS5 MATCH
        $stmt = $pdo->prepare('
            SELECT et.entry_id, et.title, et.summary, et.lang,
                   e.slug, e.image, e.category_id,
                   c.slug as category_slug,
                   snippet(entries_fts, 2, \'<mark>\', \'</mark>\', \'…\', 40) as snippet
            FROM entries_fts
            JOIN entry_translations et ON et.id = entries_fts.rowid
            JOIN entries e ON e.id = et.entry_id
            JOIN categories c ON c.id = e.category_id
            WHERE entries_fts MATCH :query
            ORDER BY rank
            LIMIT :limit
        ');
        $stmt->bindValue(':query', $query);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Get recently updated entries.
     */
    public static function recent(int $limit = 10, string $lang = null): array
    {
        $lang = $lang ?? currentLang();
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('
            SELECT e.*, et.title, et.summary,
                   c.slug as category_slug
            FROM entries e
            LEFT JOIN entry_translations et ON et.entry_id = e.id AND et.lang = :lang
            LEFT JOIN categories c ON c.id = e.category_id
            ORDER BY e.updated_at DESC
            LIMIT :limit
        ');
        $stmt->bindValue(':lang', $lang);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return self::fillFallbackTranslations($pdo, $stmt->fetchAll(), $lang);
    }

    /**
     * Fill missing translations with German fallback.
     */
    private static function fillFallbackTranslations(\PDO $pdo, array $rows, string $lang): array
    {
        if ($lang === 'de') return $rows;

        foreach ($rows as &$row) {
            if (empty($row['title'])) {
                $fb = $pdo->prepare('SELECT title, summary, body FROM entry_translations WHERE entry_id = :eid AND lang = \'de\'');
                $fb->execute([':eid' => $row['id']]);
                $fallback = $fb->fetch();
                if ($fallback) {
                    $row['title'] = $fallback['title'];
                    $row['summary'] = $fallback['summary'];
                    if (isset($row['body'])) $row['body'] = $fallback['body'];
                }
            }
        }
        return $rows;
    }
}
