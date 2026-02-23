<?php
namespace App\controllers;

use App\Auth;
use App\models\Category;
use App\models\Entry;
use App\models\Favorite;
use App\models\FileResource;

class ApiController
{
    /**
     * Search API - returns JSON results from both DB entries and file resources.
     * GET /api/search?q=query&limit=20
     */
    public static function search(): void
    {
        $query = trim($_GET['q'] ?? '');
        $limit = max(1, min(50, intval($_GET['limit'] ?? 20)));

        if ($query === '' || mb_strlen($query) < 2) {
            jsonResponse(['results' => [], 'total' => 0]);
            return;
        }

        $results = [];

        // 1. DB entries (FTS5)
        try {
            $dbResults = Entry::search($query);
            foreach (array_slice($dbResults, 0, $limit) as $r) {
                $results[] = [
                    'type'     => 'entry',
                    'title'    => $r['title'] ?? $r['slug'],
                    'summary'  => $r['summary'] ?? '',
                    'category' => $r['category_slug'],
                    'url'      => '/' . $r['category_slug'] . '/' . $r['slug'],
                    'icon'     => 'bi-journal-text',
                ];
            }
        } catch (\Exception $e) {
            // FTS syntax error — skip DB results
        }

        // 2. File resources (textures, sounds, music)
        foreach (FileResource::fileCategorySlugs() as $catSlug) {
            $fileResults = FileResource::search($catSlug, $query);
            $icon = match ($catSlug) {
                'textures' => 'bi-image',
                'sounds'   => 'bi-volume-up',
                'music'    => 'bi-music-note-beamed',
                default    => 'bi-file-earmark',
            };
            foreach (array_slice($fileResults, 0, $limit) as $f) {
                $results[] = [
                    'type'     => 'file',
                    'title'    => $f['name'],
                    'summary'  => '#' . $f['file_id'] . ' · ' . $f['extension'],
                    'category' => $catSlug,
                    'url'      => '/' . $catSlug . '/' . $f['slug'],
                    'icon'     => $icon,
                    'thumb'    => $f['type'] === 'image' ? $f['url'] : null,
                ];
            }
        }

        // Sort: entries first, then files; limit total
        $total = count($results);
        $results = array_slice($results, 0, $limit);

        jsonResponse(['results' => $results, 'total' => $total]);
    }

    /**
     * Toggle favorite (AJAX endpoint).
     * POST /api/favorite/toggle
     * Body: entry_id
     */
    public static function toggleFavorite(): void
    {
        if (!Auth::isLoggedIn()) {
            jsonResponse(['success' => false, 'error' => 'Not authenticated.'], 401);
        }

        // CSRF check (sent as header for AJAX)
        $csrfHeader = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        $csrfPost = $_POST['_csrf'] ?? '';
        $token = $csrfHeader ?: $csrfPost;

        if (!hash_equals(csrfToken(), $token)) {
            jsonResponse(['success' => false, 'error' => 'Invalid CSRF token.'], 403);
        }

        $entryId = (int)($_POST['entry_id'] ?? 0);
        if ($entryId <= 0) {
            jsonResponse(['success' => false, 'error' => 'Invalid entry ID.'], 400);
        }

        $isFavorited = Favorite::toggle(Auth::userId(), $entryId);

        jsonResponse([
            'success'     => true,
            'is_favorited'=> $isFavorited,
            'message'     => $isFavorited ? __('favorite_added') : __('favorite_removed'),
        ]);
    }

    /**
     * Paginated wiki entries for a category (JSON).
     * GET /api/entries/{category}?page=N
     */
    public static function entries(array $params): void
    {
        $slug = $params['category'] ?? '';
        $category = Category::findBySlug($slug);
        if (!$category) {
            jsonResponse(['items' => [], 'page' => 1, 'totalPages' => 1], 404);
            return;
        }

        $page = max(1, intval($_GET['page'] ?? 1));
        $perPage = 24;
        $total = Entry::countByCategory($category['id']);
        $totalPages = max(1, (int)ceil($total / $perPage));
        $page = min($page, $totalPages);
        $offset = ($page - 1) * $perPage;

        $entries = Entry::byCategory($category['id'], null, $perPage, $offset);

        $favoriteIds = [];
        if (Auth::isLoggedIn()) {
            $favoriteIds = Favorite::entryIdsForUser(Auth::userId());
        }

        $items = [];
        foreach ($entries as $e) {
            $images = json_decode($e['images_json'] ?? '[]', true) ?: [];
            $items[] = [
                'id'            => (int)$e['id'],
                'slug'          => $e['slug'],
                'title'         => $e['title'] ?? $e['slug'],
                'summary'       => $e['summary'] ?? '',
                'thumb'         => $images[0] ?? '',
                'category_slug' => $category['slug'],
                'category_icon' => $category['icon'] ?? 'bi-file-text',
                'is_favorited'  => in_array($e['id'], $favoriteIds),
                'url'           => '/' . $category['slug'] . '/' . $e['slug'],
            ];
        }

        jsonResponse([
            'items'      => $items,
            'page'       => $page,
            'totalPages' => $totalPages,
            'total'      => $total,
        ]);
    }

    /**
     * Paginated file resources for a category (JSON).
     * GET /api/files/{category}?page=N&q=query
     */
    public static function files(array $params): void
    {
        $slug = $params['category'] ?? '';
        if (!FileResource::isFileCategory($slug)) {
            jsonResponse(['items' => [], 'page' => 1, 'totalPages' => 1], 404);
            return;
        }

        $query = trim($_GET['q'] ?? '');
        $page = max(1, intval($_GET['page'] ?? 1));
        $result = FileResource::paginate($slug, $page, 48, $query);

        $items = [];
        foreach ($result['items'] as $f) {
            $items[] = [
                'slug'          => $f['slug'],
                'name'          => $f['name'],
                'file_id'       => $f['file_id'],
                'url'           => $f['url'],
                'extension'     => $f['extension'],
                'type'          => $f['type'],
                'category_slug' => $slug,
            ];
        }

        jsonResponse([
            'items'      => $items,
            'page'       => $result['pagination']['page'],
            'totalPages' => $result['pagination']['totalPages'],
            'total'      => $result['pagination']['total'],
        ]);
    }
}
