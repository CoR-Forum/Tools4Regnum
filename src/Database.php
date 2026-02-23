<?php
namespace App;

use PDO;
use PDOException;

/**
 * SQLite PDO singleton with schema auto-migration.
 */
class Database
{
    private static ?PDO $pdo = null;

    public static function getConnection(): PDO
    {
        if (self::$pdo === null) {
            $dbFile = DATA_PATH . '/tools4regnum.sqlite';
            self::$pdo = new PDO('sqlite:' . $dbFile, null, null, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
            // Enable WAL mode for better concurrent read performance
            self::$pdo->exec('PRAGMA journal_mode=WAL');
            self::$pdo->exec('PRAGMA foreign_keys=ON');
        }
        return self::$pdo;
    }

    /**
     * Run schema creation on first boot (idempotent).
     */
    public static function init(): void
    {
        $pdo = self::getConnection();

        // Check if tables exist already
        $check = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='categories'")->fetch();
        if (!$check) {
            $schema = file_get_contents(SRC_PATH . '/schema.sql');
            $pdo->exec($schema);
            return;
        }

        // Run migrations on existing databases
        self::migrate($pdo);
    }

    /**
     * Run incremental migrations on an existing database.
     */
    private static function migrate(PDO $pdo): void
    {
        // Migration 1: Rename 'image' column to 'images_json' (multi-image support)
        $cols = $pdo->query("PRAGMA table_info(entries)")->fetchAll();
        $colNames = array_column($cols, 'name');

        if (in_array('image', $colNames) && !in_array('images_json', $colNames)) {
            $pdo->exec("ALTER TABLE entries ADD COLUMN images_json TEXT DEFAULT '[]'");
            // Migrate existing single image to JSON array
            $pdo->exec("UPDATE entries SET images_json = json_array(image) WHERE image IS NOT NULL AND image != ''");
            // We can't drop columns in older SQLite, but images_json is now the source of truth
        }

        if (!in_array('images_json', $colNames) && !in_array('image', $colNames)) {
            $pdo->exec("ALTER TABLE entries ADD COLUMN images_json TEXT DEFAULT '[]'");
        }

        // Migration 2: Add 'events' category if missing
        $ev = $pdo->query("SELECT id FROM categories WHERE slug = 'events'")->fetch();
        if (!$ev) {
            $pdo->exec("INSERT INTO categories (slug, icon, sort_order) VALUES ('events', 'bi-calendar-event', 8)");
            $evId = $pdo->lastInsertId();
            $pdo->exec("INSERT INTO category_translations (category_id, lang, name, description) VALUES
                ({$evId}, 'de', 'Events', 'Spielereignisse und besondere Veranstaltungen'),
                ({$evId}, 'en', 'Events', 'In-game events and special occasions'),
                ({$evId}, 'es', 'Eventos', 'Eventos del juego y ocasiones especiales')");
        }
    }
}
