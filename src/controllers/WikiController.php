<?php
namespace App\controllers;

use App\Auth;
use App\models\Category;
use App\models\Entry;
use App\models\Favorite;
use App\models\FileResource;

class WikiController
{
    /**
     * Show entries for a category.
     */
    public static function category(array $params): void
    {
        $slug = $params['category'] ?? '';
        $category = Category::findBySlug($slug);

        if (!$category) {
            http_response_code(404);
            renderWithLayout('errors/404', [], __('error_404'));
            return;
        }

        $total = Entry::countByCategory($category['id']);
        $pagination = paginate($total);

        $entries = Entry::byCategory(
            $category['id'],
            null,
            $pagination['limit'],
            $pagination['offset']
        );

        // Check favorites for logged-in users
        $favoriteIds = [];
        if (Auth::isLoggedIn()) {
            $favoriteIds = Favorite::entryIdsForUser(Auth::userId());
        }

        renderWithLayout('wiki/category', [
            'category'    => $category,
            'entries'     => $entries,
            'pagination'  => $pagination,
            'favoriteIds' => $favoriteIds,
        ], $category['name']);
    }

    /**
     * Show a single entry.
     */
    public static function entry(array $params): void
    {
        $catSlug   = $params['category'] ?? '';
        $entrySlug = $params['slug'] ?? '';

        $entry = Entry::findBySlugs($catSlug, $entrySlug);

        if (!$entry) {
            http_response_code(404);
            renderWithLayout('errors/404', [], __('error_404'));
            return;
        }

        $category = Category::findBySlug($catSlug);
        $isFavorited = false;
        if (Auth::isLoggedIn()) {
            $isFavorited = Favorite::isFavorited(Auth::userId(), $entry['id']);
        }

        renderWithLayout('wiki/entry', [
            'entry'       => $entry,
            'category'    => $category,
            'isFavorited' => $isFavorited,
        ], $entry['title'] ?? $entrySlug);
    }

    /**
     * Search across entries.
     */
    public static function search(): void
    {
        $query = trim($_GET['q'] ?? '');
        $dbResults = [];
        $fileResults = [];

        if ($query !== '') {
            // Database entries (FTS5)
            try {
                $dbResults = Entry::search($query);
            } catch (\Exception $e) {
                $dbResults = [];
            }

            // File resources (textures, sounds, music)
            foreach (FileResource::fileCategorySlugs() as $catSlug) {
                $found = FileResource::search($catSlug, $query);
                foreach ($found as $f) {
                    $f['category_slug'] = $catSlug;
                    $f['is_file'] = true;
                    $fileResults[] = $f;
                }
            }
        }

        renderWithLayout('wiki/search', [
            'query'       => $query,
            'dbResults'   => $dbResults,
            'fileResults' => $fileResults,
        ], __('search_results'));
    }
}
