<?php
/**
 * Category listing template — shows entries in a category.
 *
 * Variables: $category, $entries, $pagination, $favoriteIds
 */
?>
<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><?= e(__('home')) ?></a></li>
            <li class="breadcrumb-item active"><?= e($category['name'] ?? $category['slug']) ?></li>
        </ol>
    </nav>

    <div class="d-flex align-items-center mb-4">
        <i class="bi <?= e($category['icon'] ?? 'bi-folder') ?> display-5 text-primary me-3"></i>
        <div>
            <h1 class="mb-0"><?= e($category['name'] ?? $category['slug']) ?></h1>
            <?php if (!empty($category['description'])): ?>
                <p class="text-muted mb-0"><?= e($category['description']) ?></p>
            <?php endif; ?>
        </div>
    </div>

    <?php if (empty($entries)): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle me-2"></i><?= e(__('no_entries')) ?>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($entries as $entry): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 shadow-sm border-0 entry-card">
                        <?php $thumb = json_decode($entry['images_json'] ?? '[]', true)[0] ?? ($entry['image'] ?? ''); ?>
                        <?php if (!empty($thumb)): ?>
                            <img src="<?= e($thumb) ?>" class="card-img-top" alt="<?= e($entry['title'] ?? '') ?>"
                                 style="height: 180px; object-fit: cover;">
                        <?php else: ?>
                            <div class="card-img-top bg-secondary d-flex align-items-center justify-content-center"
                                 style="height: 180px;">
                                <i class="bi <?= e($category['icon'] ?? 'bi-file-text') ?> text-white display-5"></i>
                            </div>
                        <?php endif; ?>
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <h5 class="card-title mb-1"><?= e($entry['title'] ?? $entry['slug']) ?></h5>
                                <?php if (\App\Auth::isLoggedIn()): ?>
                                    <button class="btn btn-sm btn-link text-danger p-0 favorite-btn"
                                            data-entry-id="<?= (int)$entry['id'] ?>"
                                            title="<?= in_array($entry['id'], $favoriteIds) ? e(__('remove_favorite')) : e(__('add_favorite')) ?>">
                                        <i class="bi <?= in_array($entry['id'], $favoriteIds) ? 'bi-heart-fill' : 'bi-heart' ?>"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($entry['summary'])): ?>
                                <p class="card-text small text-muted"><?= e(mb_strimwidth($entry['summary'], 0, 120, '…')) ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="card-footer bg-transparent border-0">
                            <a href="/<?= e($category['slug']) ?>/<?= e($entry['slug']) ?>"
                               class="btn btn-sm btn-outline-primary"><?= e(__('more')) ?> &rarr;</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($pagination['totalPages'] > 1): ?>
            <nav class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php for ($p = 1; $p <= $pagination['totalPages']; $p++): ?>
                        <li class="page-item <?= $p === $pagination['page'] ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $p ?>"><?= $p ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</div>
