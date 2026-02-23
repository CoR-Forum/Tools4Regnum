<?php
namespace App\controllers;

use App\Auth;
use App\models\Category;
use App\models\FileResource;
use App\models\Favorite;

/**
 * Controller for file-based resource categories (textures, sounds, music).
 * These are loaded from data/files.json, not from the database.
 */
class FileResourceController
{
    /**
     * Show a paginated listing of file resources in a category.
     */
    public static function category(array $params): void
    {
        $slug = $params['category'] ?? '';

        if (!FileResource::isFileCategory($slug)) {
            http_response_code(404);
            renderWithLayout('errors/404', [], __('error_404'));
            return;
        }

        $category = Category::findBySlug($slug);
        if (!$category) {
            http_response_code(404);
            renderWithLayout('errors/404', [], __('error_404'));
            return;
        }

        $query = trim($_GET['q'] ?? '');
        $page  = max(1, intval($_GET['page'] ?? 1));

        $result = FileResource::paginate($slug, $page, 48, $query);

        renderWithLayout('wiki/file_category', [
            'category'   => $category,
            'items'      => $result['items'],
            'pagination' => $result['pagination'],
            'query'      => $query,
            'lastUpdate' => FileResource::lastUpdate(),
        ], $category['name']);
    }

    /**
     * Show a single file resource detail.
     */
    public static function detail(array $params): void
    {
        $catSlug  = $params['category'] ?? '';
        $itemSlug = $params['slug'] ?? '';

        if (!FileResource::isFileCategory($catSlug)) {
            http_response_code(404);
            renderWithLayout('errors/404', [], __('error_404'));
            return;
        }

        $category = Category::findBySlug($catSlug);
        $item = FileResource::findBySlug($catSlug, $itemSlug);

        if (!$item || !$category) {
            http_response_code(404);
            renderWithLayout('errors/404', [], __('error_404'));
            return;
        }

        renderWithLayout('wiki/file_detail', [
            'category' => $category,
            'item'     => $item,
        ], $item['name']);
    }
}
