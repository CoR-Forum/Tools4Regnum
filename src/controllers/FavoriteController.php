<?php
namespace App\controllers;

use App\Auth;
use App\models\Favorite;

class FavoriteController
{
    public static function index(): void
    {
        requireAuth();

        $favorites = Favorite::forUser(Auth::userId());

        // Group by category
        $grouped = [];
        foreach ($favorites as $fav) {
            $catName = $fav['category_name'] ?? 'Other';
            $grouped[$catName][] = $fav;
        }

        renderWithLayout('favorites', [
            'favorites' => $favorites,
            'grouped'   => $grouped,
        ], __('favorites'));
    }
}
