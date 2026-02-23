<?php
/**
 * Tools4Regnum — Application Bootstrap
 *
 * Initializes session, loads environment, sets up autoloading,
 * database, and i18n.
 */

// Error reporting based on APP_DEBUG
$debug = getenv('APP_DEBUG') === 'true';
if ($debug) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Base paths
define('BASE_PATH', dirname(__DIR__));
define('SRC_PATH', BASE_PATH . '/src');
define('TEMPLATE_PATH', BASE_PATH . '/templates');
define('LANG_PATH', BASE_PATH . '/lang');
define('DATA_PATH', BASE_PATH . '/data');
define('PUBLIC_PATH', BASE_PATH . '/public');
define('UPLOAD_PATH', PUBLIC_PATH . '/uploads');

// Environment helpers
function env(string $key, string $default = ''): string {
    return getenv($key) ?: $default;
}

function envArray(string $key): array {
    $val = env($key);
    return $val !== '' ? array_map('trim', explode(',', $val)) : [];
}

// Simple PSR-4-ish autoloader for src/ namespace "App\"
spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $file = SRC_PATH . '/' . str_replace('\\', '/', $relative) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Load helpers
require_once SRC_PATH . '/helpers/functions.php';
require_once SRC_PATH . '/helpers/i18n.php';

// Initialize database (creates tables on first run)
\App\Database::init();

// Set default language
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = env('DEFAULT_LANG', 'de');
}

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
