<?php
/**
 * Admin dashboard template.
 *
 * Variables: $totalEntries, $totalUsers, $totalFavorites, $categories, $recentEntries
 */
?>
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="bi bi-gear-fill me-2"></i><?= e(__('admin_dashboard')) ?></h1>
        <a href="/admin/entry/new" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i><?= e(__('create_entry')) ?>
        </a>
    </div>

    <!-- Stats -->
    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body">
                    <i class="bi bi-file-text display-4 text-primary"></i>
                    <h3 class="mt-2"><?= $totalEntries ?></h3>
                    <p class="text-muted mb-0"><?= e(__('total_entries')) ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body">
                    <i class="bi bi-people display-4 text-success"></i>
                    <h3 class="mt-2"><?= $totalUsers ?></h3>
                    <p class="text-muted mb-0"><?= e(__('total_users')) ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body">
                    <i class="bi bi-heart display-4 text-danger"></i>
                    <h3 class="mt-2"><?= $totalFavorites ?></h3>
                    <p class="text-muted mb-0"><?= e(__('total_favorites')) ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Categories overview -->
    <h4 class="mb-3"><i class="bi bi-grid me-2"></i><?= e(__('admin_categories')) ?></h4>
    <div class="table-responsive mb-5">
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Slug</th>
                    <th><?= e(__('title')) ?></th>
                    <th><?= e(__('entries')) ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $cat): ?>
                    <tr>
                        <td><?= (int)$cat['id'] ?></td>
                        <td><code><?= e($cat['slug']) ?></code></td>
                        <td><?= e($cat['name'] ?? '—') ?></td>
                        <td>
                            <span class="badge bg-secondary"><?= \App\models\Category::entryCount($cat['id']) ?></span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Recent entries -->
    <h4 class="mb-3"><i class="bi bi-clock-history me-2"></i><?= e(__('recent_entries')) ?></h4>
    <?php if (empty($recentEntries)): ?>
        <p class="text-muted"><?= e(__('no_entries')) ?></p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th><?= e(__('title')) ?></th>
                        <th><?= e(__('category')) ?></th>
                        <th><?= e(__('updated_at')) ?></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentEntries as $entry): ?>
                        <tr>
                            <td><?= (int)$entry['id'] ?></td>
                            <td><?= e($entry['title'] ?? $entry['slug']) ?></td>
                            <td><code><?= e($entry['category_slug'] ?? '') ?></code></td>
                            <td><?= e($entry['updated_at'] ?? '—') ?></td>
                            <td>
                                <a href="/admin/entry/<?= (int)$entry['id'] ?>/edit"
                                   class="btn btn-sm btn-outline-warning" title="<?= e(__('edit_entry')) ?>">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="/<?= e($entry['category_slug']) ?>/<?= e($entry['slug']) ?>"
                                   class="btn btn-sm btn-outline-info" title="View" target="_blank">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
