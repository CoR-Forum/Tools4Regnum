<?php
/**
 * Favorites page template.
 *
 * Variables: $favorites, $grouped
 */
?>
<div class="container py-4">
    <h1><i class="bi bi-heart-fill text-danger me-2"></i><?= e(__('favorites')) ?></h1>

    <?php if (empty($favorites)): ?>
        <div class="alert alert-info mt-3">
            <i class="bi bi-info-circle me-2"></i><?= e(__('no_favorites')) ?>
        </div>
    <?php else: ?>
        <?php foreach ($grouped as $categoryName => $items): ?>
            <h4 class="mt-4 mb-3"><?= e($categoryName) ?></h4>
            <div class="row g-3">
                <?php foreach ($items as $fav): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100 shadow-sm border-0">
                            <?php $thumb = json_decode($fav['images_json'] ?? '[]', true)[0] ?? ($fav['image'] ?? ''); ?>
                            <?php if (!empty($thumb)): ?>
                                <img src="<?= e($thumb) ?>" class="card-img-top"
                                     alt="<?= e($fav['title'] ?? '') ?>"
                                     style="height: 140px; object-fit: cover;">
                            <?php endif; ?>
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <h6 class="card-title mb-1"><?= e($fav['title'] ?? $fav['slug']) ?></h6>
                                    <button class="btn btn-sm btn-link text-danger p-0 favorite-btn"
                                            data-entry-id="<?= (int)$fav['id'] ?>">
                                        <i class="bi bi-heart-fill"></i>
                                    </button>
                                </div>
                                <?php if (!empty($fav['summary'])): ?>
                                    <p class="card-text small text-muted"><?= e(mb_strimwidth($fav['summary'], 0, 100, '…')) ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="card-footer bg-transparent border-0">
                                <a href="/<?= e($fav['category_slug']) ?>/<?= e($fav['slug']) ?>"
                                   class="btn btn-sm btn-outline-primary"><?= e(__('more')) ?> &rarr;</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
