<?php
/**
 * Tools4Regnum — Front Controller
 *
 * All requests are routed through this file via .htaccess.
 */

// Bootstrap the application
require_once __DIR__ . '/../src/bootstrap.php';

use App\Router;
use App\controllers\HomeController;
use App\controllers\AuthController;
use App\controllers\WikiController;
use App\controllers\FavoriteController;
use App\controllers\AdminController;
use App\controllers\ApiController;
use App\controllers\FileResourceController;
use App\models\FileResource;

// ============================================================
// Routes
// ============================================================
$router = new Router();

// Home
$router->get('/', [HomeController::class, 'index']);

// Auth
$router->get('/login',  [AuthController::class, 'loginForm']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/logout', [AuthController::class, 'logout']);

// Language switch
$router->get('/lang/{code}', function (array $params) {
    $code = $params['code'] ?? 'de';
    $allowed = array_keys(availableLanguages());
    if (in_array($code, $allowed, true)) {
        $_SESSION['lang'] = $code;
    }
    // Redirect back to referrer or home
    $back = $_SERVER['HTTP_REFERER'] ?? '/';
    redirect($back);
});

// Search
$router->get('/search', [WikiController::class, 'search']);

// Favorites
$router->get('/favorites', [FavoriteController::class, 'index']);

// API endpoints (AJAX)
$router->get('/api/search', [ApiController::class, 'search']);
$router->post('/api/favorite/toggle', [ApiController::class, 'toggleFavorite']);

// Admin
$router->get('/admin',                  [AdminController::class, 'dashboard']);
$router->get('/admin/entry/new',        [AdminController::class, 'createForm']);
$router->post('/admin/entry',           [AdminController::class, 'store']);
$router->get('/admin/entry/{id}/edit',  [AdminController::class, 'editForm']);
$router->post('/admin/entry/{id}',      [AdminController::class, 'update']);
$router->post('/admin/entry/{id}/delete', [AdminController::class, 'delete']);

// File-based resource categories (textures, sounds, music) — before generic wiki
foreach (FileResource::fileCategorySlugs() as $fSlug) {
    $capturedSlug = $fSlug; // capture for closure
    $router->get('/' . $fSlug, function (array $params) use ($capturedSlug) {
        $params['category'] = $capturedSlug;
        FileResourceController::category($params);
    });
    $router->get('/' . $fSlug . '/{slug}', function (array $params) use ($capturedSlug) {
        $params['category'] = $capturedSlug;
        FileResourceController::detail($params);
    });
}

// Wiki pages (must be last — catches /{category} and /{category}/{slug})
$router->get('/{category}',        [WikiController::class, 'category']);
$router->get('/{category}/{slug}', [WikiController::class, 'entry']);

// Dispatch
$router->dispatch();
