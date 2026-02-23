<?php
namespace App\controllers;

use App\Auth;
use App\Database;
use App\models\Category;
use App\models\Entry;
use App\models\User;
use App\models\Favorite;

class AdminController
{
    /**
     * Admin dashboard.
     */
    public static function dashboard(): void
    {
        requireAdmin();

        $pdo = Database::getConnection();
        $totalEntries   = (int)$pdo->query('SELECT COUNT(*) FROM entries')->fetchColumn();
        $totalUsers     = User::count();
        $totalFavorites = (int)$pdo->query('SELECT COUNT(*) FROM favorites')->fetchColumn();
        $categories     = Category::all();
        $recentEntries  = Entry::recent(10);

        renderWithLayout('admin/dashboard', [
            'totalEntries'   => $totalEntries,
            'totalUsers'     => $totalUsers,
            'totalFavorites' => $totalFavorites,
            'categories'     => $categories,
            'recentEntries'  => $recentEntries,
        ], __('admin_dashboard'));
    }

    /**
     * Show entry creation form.
     */
    public static function createForm(): void
    {
        requireAdmin();

        $categories = Category::all();

        renderWithLayout('admin/entry_form', [
            'entry'        => null,
            'translations' => [],
            'categories'   => $categories,
            'formAction'   => '/admin/entry',
            'formTitle'    => __('create_entry'),
        ], __('create_entry'));
    }

    /**
     * Store a new entry.
     */
    public static function store(): void
    {
        requireAdmin();
        requireCsrf();

        $data = [
            'category_id'      => (int)($_POST['category_id'] ?? 0),
            'slug'             => slugify($_POST['slug'] ?? $_POST['title_de'] ?? ''),
            'images'           => [],
            'data'             => json_decode($_POST['data_json'] ?? '{}', true) ?: [],
            'created_by_userid'=> Auth::userId(),
        ];

        // Handle multiple image uploads
        $data['images'] = self::handleMultipleImageUploads($data['slug']);

        $translations = self::extractTranslations();

        if (empty($data['slug'])) {
            $data['slug'] = slugify($translations['de']['title'] ?? $translations['en']['title'] ?? 'entry');
        }

        try {
            $entryId = Entry::create($data, $translations);
            flash('success', __('entry_created'));
            redirect('/admin/entry/' . $entryId . '/edit');
        } catch (\Exception $e) {
            flash('danger', 'Error: ' . $e->getMessage());
            redirect('/admin/entry/new');
        }
    }

    /**
     * Show entry edit form.
     */
    public static function editForm(array $params): void
    {
        requireAdmin();

        $id = (int)($params['id'] ?? 0);
        $entry = Entry::find($id);

        if (!$entry) {
            http_response_code(404);
            renderWithLayout('errors/404', [], __('error_404'));
            return;
        }

        $translations = Entry::getTranslations($id);
        $categories   = Category::all();

        renderWithLayout('admin/entry_form', [
            'entry'        => $entry,
            'translations' => $translations,
            'categories'   => $categories,
            'formAction'   => '/admin/entry/' . $id,
            'formTitle'    => __('edit_entry'),
        ], __('edit_entry'));
    }

    /**
     * Update an existing entry.
     */
    public static function update(array $params): void
    {
        requireAdmin();
        requireCsrf();

        $id = (int)($params['id'] ?? 0);
        $existing = Entry::find($id);
        if (!$existing) {
            redirect('/admin');
            return;
        }

        // Existing images
        $existingImages = json_decode($existing['images_json'] ?? '[]', true) ?: [];

        // Handle image deletions
        $deleteImages = $_POST['delete_images'] ?? [];
        if (!empty($deleteImages)) {
            $existingImages = array_values(array_filter($existingImages, function($img) use ($deleteImages) {
                return !in_array($img, $deleteImages, true);
            }));
        }

        $data = [
            'category_id' => (int)($_POST['category_id'] ?? $existing['category_id']),
            'slug'        => $_POST['slug'] ?? $existing['slug'],
            'images'      => $existingImages,
            'data'        => json_decode($_POST['data_json'] ?? '{}', true) ?: [],
        ];

        // Handle new image uploads — append to existing
        $newImages = self::handleMultipleImageUploads($data['slug']);
        $data['images'] = array_merge($data['images'], $newImages);

        $translations = self::extractTranslations();

        try {
            Entry::update($id, $data, $translations);
            flash('success', __('entry_updated'));
        } catch (\Exception $e) {
            flash('danger', 'Error: ' . $e->getMessage());
        }

        redirect('/admin/entry/' . $id . '/edit');
    }

    /**
     * Delete an entry.
     */
    public static function delete(array $params): void
    {
        requireAdmin();
        requireCsrf();

        $id = (int)($params['id'] ?? 0);
        Entry::delete($id);
        flash('success', __('entry_deleted'));
        redirect('/admin');
    }

    /**
     * Extract translations from POST data.
     */
    private static function extractTranslations(): array
    {
        $translations = [];
        foreach (['de', 'en', 'es'] as $lang) {
            $title = trim($_POST['title_' . $lang] ?? '');
            if ($title !== '') {
                $translations[$lang] = [
                    'title'   => $title,
                    'summary' => trim($_POST['summary_' . $lang] ?? ''),
                    'body'    => $_POST['body_' . $lang] ?? '',
                ];
            }
        }
        return $translations;
    }

    /**
     * Handle multiple image uploads. Returns array of relative paths.
     */
    private static function handleMultipleImageUploads(string $slug): array
    {
        $uploaded = [];
        if (!isset($_FILES['images']) || !is_array($_FILES['images']['name'])) {
            return $uploaded;
        }

        $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        $dir = UPLOAD_PATH . '/entries';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $count = count($_FILES['images']['name']);
        for ($i = 0; $i < $count; $i++) {
            if ($_FILES['images']['error'][$i] !== UPLOAD_ERR_OK) {
                continue;
            }
            $type = $_FILES['images']['type'][$i];
            if (!in_array($type, $allowed, true)) {
                continue;
            }

            $ext = match ($type) {
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/webp' => 'webp',
                'image/gif'  => 'gif',
                default      => 'jpg',
            };

            $filename = $slug . '-' . time() . '-' . $i . '.' . $ext;
            $destination = $dir . '/' . $filename;

            if (move_uploaded_file($_FILES['images']['tmp_name'][$i], $destination)) {
                $uploaded[] = '/uploads/entries/' . $filename;
            }
        }

        return $uploaded;
    }
}
