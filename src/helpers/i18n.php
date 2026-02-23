<?php
/**
 * i18n — Internationalization helper
 */

/** @var array<string, array<string, string>> */
$_LANG_CACHE = [];

/**
 * Load language strings for the given locale.
 */
function loadLang(string $lang): array
{
    global $_LANG_CACHE;
    if (!isset($_LANG_CACHE[$lang])) {
        $file = LANG_PATH . '/' . $lang . '.php';
        if (file_exists($file)) {
            $_LANG_CACHE[$lang] = require $file;
        } else {
            $_LANG_CACHE[$lang] = [];
        }
    }
    return $_LANG_CACHE[$lang];
}

/**
 * Translate a key. Falls back to 'de', then returns key itself.
 */
function __($key, array $replace = []): string {
    $lang = $_SESSION['lang'] ?? 'de';
    $strings = loadLang($lang);

    $text = $strings[$key] ?? null;

    // Fallback to German
    if ($text === null && $lang !== 'de') {
        $strings = loadLang('de');
        $text = $strings[$key] ?? null;
    }

    // Fallback to key itself
    if ($text === null) {
        $text = $key;
    }

    // Simple placeholder replacement: :name
    foreach ($replace as $placeholder => $value) {
        $text = str_replace(':' . $placeholder, (string)$value, $text);
    }

    return $text;
}

/**
 * Echo a translated, HTML-escaped string.
 */
function _e(string $key, array $replace = []): void
{
    echo e(__($key, $replace));
}

/**
 * Get current language code.
 */
function currentLang(): string
{
    return $_SESSION['lang'] ?? 'de';
}

/**
 * Available languages.
 */
function availableLanguages(): array
{
    return [
        'de' => ['name' => 'Deutsch',  'flag' => '🇩🇪'],
        'en' => ['name' => 'English',  'flag' => '🇬🇧'],
        'es' => ['name' => 'Español',  'flag' => '🇪🇸'],
    ];
}
