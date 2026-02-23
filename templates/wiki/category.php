<?php
/**
 * Category listing template — shows entries in a category.
 *
 * Variables: $category, $entries, $pagination, $favoriteIds
 */
$isLoggedIn = \App\Auth::isLoggedIn();
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
        <div class="row g-4 infinite-scroll-container"
             data-infinite-url="/api/entries/<?= e($category['slug']) ?>"
             data-infinite-page="<?= $pagination['page'] ?>"
             data-infinite-total-pages="<?= $pagination['totalPages'] ?>"
             data-infinite-type="entries"
             data-category-slug="<?= e($category['slug']) ?>"
             data-category-icon="<?= e($category['icon'] ?? 'bi-file-text') ?>"
             data-is-logged-in="<?= $isLoggedIn ? '1' : '0' ?>">
            <?php foreach ($entries as $entry): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 shadow-sm border-0 entry-card">
                        <?php $thumb = json_decode($entry['images_json'] ?? '[]', true)[0] ?? ''; ?>
                        <?php if (!empty($thumb)): ?>
                            <img src="<?= e($thumb) ?>" class="card-img-top" alt="<?= e($entry['title'] ?? '') ?>"
                                 style="height: 180px; object-fit: cover;" loading="lazy">
                        <?php else: ?>
                            <div class="card-img-top bg-secondary d-flex align-items-center justify-content-center"
                                 style="height: 180px;">
                                <i class="bi <?= e($category['icon'] ?? 'bi-file-text') ?> text-white display-5"></i>
                            </div>
                        <?php endif; ?>
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <h5 class="card-title mb-1"><?= e($entry['title'] ?? $entry['slug']) ?></h5>
                                <?php if ($isLoggedIn): ?>
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

        <!-- Infinite scroll sentinel -->
        <div class="infinite-scroll-sentinel"></div>
        <div class="infinite-scroll-loader text-center py-4" style="display:none;">
            <div class="spinner-border text-secondary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
        <div class="infinite-scroll-end text-center py-3" style="display:none;">
            <small class="text-muted"><i class="bi bi-check-circle me-1"></i><?= e(__('all_loaded')) ?></small>
        </div>

        <!-- Pagination (shown when infinite scroll is off) -->
        <?php if ($pagination['totalPages'] > 1): ?>
            <nav class="mt-4 pagination-nav" style="display:none;">
                <ul class="pagination justify-content-center flex-wrap">
                    <?php
                    $p = $pagination['page'];
                    $tp = $pagination['totalPages'];
                    if ($p > 1): ?>
                        <li class="page-item"><a class="page-link" href="?page=<?= $p - 1 ?>">&laquo;</a></li>
                    <?php endif;
                    $start = max(1, $p - 5);
                    $end = min($tp, $p + 5);
                    if ($start > 1): ?>
                        <li class="page-item"><a class="page-link" href="?page=1">1</a></li>
                        <?php if ($start > 2): ?><li class="page-item disabled"><span class="page-link">&hellip;</span></li><?php endif;
                    endif;
                    for ($i = $start; $i <= $end; $i++): ?>
                        <li class="page-item <?= $i === $p ? 'active' : '' ?>"><a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a></li>
                    <?php endfor;
                    if ($end < $tp): ?>
                        <?php if ($end < $tp - 1): ?><li class="page-item disabled"><span class="page-link">&hellip;</span></li><?php endif; ?>
                        <li class="page-item"><a class="page-link" href="?page=<?= $tp ?>"><?= $tp ?></a></li>
                    <?php endif;
                    if ($p < $tp): ?>
                        <li class="page-item"><a class="page-link" href="?page=<?= $p + 1 ?>">&raquo;</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</div>
