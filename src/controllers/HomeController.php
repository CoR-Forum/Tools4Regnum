<?php
namespace App\controllers;

use App\models\Category;
use App\models\Entry;
use App\models\FileResource;

class HomeController
{
    public static function index(): void
    {
        $categories = Category::all();

        // Attach entry counts (use FileResource for file-based categories)
        foreach ($categories as &$cat) {
            if (FileResource::isFileCategory($cat['slug'])) {
                $cat['entry_count'] = FileResource::count($cat['slug']);
            } else {
                $cat['entry_count'] = Category::entryCount($cat['id']);
            }
        }

        $recentEntries = Entry::recent(8);

        renderWithLayout('home', [
            'categories'    => $categories,
            'recentEntries' => $recentEntries,
        ], __('home'));
    }
}
