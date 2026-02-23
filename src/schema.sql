-- Tools4Regnum SQLite Schema
-- ============================================================

-- Categories (npc, quest, item, monster, texture, sound, music, …)
CREATE TABLE IF NOT EXISTS categories (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    slug        TEXT NOT NULL UNIQUE,
    icon        TEXT DEFAULT NULL,
    sort_order  INTEGER DEFAULT 0,
    created_at  TEXT DEFAULT (datetime('now')),
    updated_at  TEXT DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS category_translations (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    category_id  INTEGER NOT NULL REFERENCES categories(id) ON DELETE CASCADE,
    lang         TEXT NOT NULL,  -- de, en, es
    name         TEXT NOT NULL,
    description  TEXT DEFAULT '',
    UNIQUE(category_id, lang)
);

-- Wiki Entries
CREATE TABLE IF NOT EXISTS entries (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    category_id     INTEGER NOT NULL REFERENCES categories(id) ON DELETE CASCADE,
    slug            TEXT NOT NULL,
    images_json     TEXT DEFAULT '[]',
    data_json       TEXT DEFAULT '{}',
    created_at      TEXT DEFAULT (datetime('now')),
    updated_at      TEXT DEFAULT (datetime('now')),
    created_by_userid INTEGER DEFAULT 0,
    UNIQUE(category_id, slug)
);

CREATE TABLE IF NOT EXISTS entry_translations (
    id        INTEGER PRIMARY KEY AUTOINCREMENT,
    entry_id  INTEGER NOT NULL REFERENCES entries(id) ON DELETE CASCADE,
    lang      TEXT NOT NULL,
    title     TEXT NOT NULL,
    summary   TEXT DEFAULT '',
    body      TEXT DEFAULT '',
    UNIQUE(entry_id, lang)
);

-- Tags
CREATE TABLE IF NOT EXISTS tags (
    id   INTEGER PRIMARY KEY AUTOINCREMENT,
    slug TEXT NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS tag_translations (
    id      INTEGER PRIMARY KEY AUTOINCREMENT,
    tag_id  INTEGER NOT NULL REFERENCES tags(id) ON DELETE CASCADE,
    lang    TEXT NOT NULL,
    name    TEXT NOT NULL,
    UNIQUE(tag_id, lang)
);

CREATE TABLE IF NOT EXISTS entry_tags (
    entry_id INTEGER NOT NULL REFERENCES entries(id) ON DELETE CASCADE,
    tag_id   INTEGER NOT NULL REFERENCES tags(id) ON DELETE CASCADE,
    PRIMARY KEY (entry_id, tag_id)
);

-- Users (local cache of forum users)
CREATE TABLE IF NOT EXISTS users (
    forum_user_id  INTEGER PRIMARY KEY,
    username       TEXT NOT NULL,
    email          TEXT DEFAULT '',
    is_admin       INTEGER DEFAULT 0,
    last_login     TEXT DEFAULT (datetime('now'))
);

-- Favorites
CREATE TABLE IF NOT EXISTS favorites (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id    INTEGER NOT NULL,
    entry_id   INTEGER NOT NULL REFERENCES entries(id) ON DELETE CASCADE,
    created_at TEXT DEFAULT (datetime('now')),
    UNIQUE(user_id, entry_id)
);

-- Full-Text Search (FTS5) for entry translations
CREATE VIRTUAL TABLE IF NOT EXISTS entries_fts USING fts5(
    title, summary, body,
    content='entry_translations',
    content_rowid='id'
);

-- Triggers to keep FTS in sync
CREATE TRIGGER IF NOT EXISTS entry_translations_ai AFTER INSERT ON entry_translations BEGIN
    INSERT INTO entries_fts(rowid, title, summary, body)
    VALUES (new.id, new.title, new.summary, new.body);
END;

CREATE TRIGGER IF NOT EXISTS entry_translations_ad AFTER DELETE ON entry_translations BEGIN
    INSERT INTO entries_fts(entries_fts, rowid, title, summary, body)
    VALUES ('delete', old.id, old.title, old.summary, old.body);
END;

CREATE TRIGGER IF NOT EXISTS entry_translations_au AFTER UPDATE ON entry_translations BEGIN
    INSERT INTO entries_fts(entries_fts, rowid, title, summary, body)
    VALUES ('delete', old.id, old.title, old.summary, old.body);
    INSERT INTO entries_fts(rowid, title, summary, body)
    VALUES (new.id, new.title, new.summary, new.body);
END;

-- Seed default categories
INSERT INTO categories (slug, icon, sort_order) VALUES
    ('npcs',      'bi-person-fill',    1),
    ('quests',    'bi-journal-text',   2),
    ('items',     'bi-box-seam',       3),
    ('monsters',  'bi-bug-fill',       4),
    ('textures',  'bi-palette-fill',   5),
    ('sounds',    'bi-volume-up-fill', 6),
    ('music',     'bi-music-note-beamed', 7),
    ('events',    'bi-calendar-event', 8);

-- Seed category translations (DE)
INSERT INTO category_translations (category_id, lang, name, description) VALUES
    (1, 'de', 'NPCs',      'Nicht-Spieler-Charaktere in der Welt von Regnum'),
    (2, 'de', 'Quests',    'Aufgaben und Abenteuer'),
    (3, 'de', 'Items',     'Gegenstände, Ausrüstung und Materialien'),
    (4, 'de', 'Monster',   'Kreaturen und Gegner'),
    (5, 'de', 'Texturen',  'Spieltexturen und Grafiken'),
    (6, 'de', 'Sounds',    'Soundeffekte'),
    (7, 'de', 'Musik',     'Spielmusik und Soundtrack'),
    (8, 'de', 'Events',    'Spielereignisse und besondere Veranstaltungen');

-- Seed category translations (EN)
INSERT INTO category_translations (category_id, lang, name, description) VALUES
    (1, 'en', 'NPCs',      'Non-player characters in the world of Regnum'),
    (2, 'en', 'Quests',    'Tasks and adventures'),
    (3, 'en', 'Items',     'Items, equipment and materials'),
    (4, 'en', 'Monsters',  'Creatures and enemies'),
    (5, 'en', 'Textures',  'Game textures and graphics'),
    (6, 'en', 'Sounds',    'Sound effects'),
    (7, 'en', 'Music',     'Game music and soundtrack'),
    (8, 'en', 'Events',    'In-game events and special occasions');

-- Seed category translations (ES)
INSERT INTO category_translations (category_id, lang, name, description) VALUES
    (1, 'es', 'NPCs',        'Personajes no jugables en el mundo de Regnum'),
    (2, 'es', 'Misiones',    'Tareas y aventuras'),
    (3, 'es', 'Objetos',     'Objetos, equipamiento y materiales'),
    (4, 'es', 'Monstruos',   'Criaturas y enemigos'),
    (5, 'es', 'Texturas',    'Texturas y gráficos del juego'),
    (6, 'es', 'Sonidos',     'Efectos de sonido'),
    (7, 'es', 'Música',      'Música y banda sonora del juego'),
    (8, 'es', 'Eventos',     'Eventos del juego y ocasiones especiales');

-- Indexes
CREATE INDEX IF NOT EXISTS idx_entries_category ON entries(category_id);
CREATE INDEX IF NOT EXISTS idx_entry_translations_entry ON entry_translations(entry_id);
CREATE INDEX IF NOT EXISTS idx_entry_translations_lang ON entry_translations(lang);
CREATE INDEX IF NOT EXISTS idx_favorites_user ON favorites(user_id);
CREATE INDEX IF NOT EXISTS idx_favorites_entry ON favorites(entry_id);
