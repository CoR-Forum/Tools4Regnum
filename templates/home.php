<?php
/**
 * Home page template.
 *
 * Variables: $categories, $recentEntries
 */
?>

<!-- Hero -->
<section class="hero-section py-5 text-center text-white">
    <div class="container">
        <h1 class="display-4 fw-bold"><i class="bi bi-shield-fill me-2"></i><?= e(__('app_name')) ?></h1>
        <p class="lead"><?= e(__('app_subtitle')) ?></p>
        <form action="/search" method="GET" class="row justify-content-center mt-4">
            <div class="col-md-6 live-search-wrapper">
                <div class="input-group input-group-lg">
                    <input type="search" class="form-control live-search-input" name="q"
                           placeholder="<?= e(__('search_placeholder')) ?>"
                           autocomplete="off">
                    <button class="btn btn-light" type="submit">
                        <i class="bi bi-search"></i> <?= e(__('search')) ?>
                    </button>
                </div>
                <div class="live-search-dropdown"></div>
            </div>
        </form>
    </div>
</section>

<!-- Categories -->
<section class="py-5">
    <div class="container">
        <h2 class="mb-4"><i class="bi bi-grid-fill me-2"></i><?= e(__('categories')) ?></h2>
        <div class="row g-4">
            <?php foreach ($categories as $cat): ?>
                <div class="col-6 col-md-4 col-lg-3">
                    <a href="/<?= e($cat['slug']) ?>" class="text-decoration-none">
                        <div class="card category-card h-100 text-center border-0 shadow-sm">
                            <div class="card-body">
                                <i class="bi <?= e($cat['icon'] ?? 'bi-folder') ?> display-4 text-primary"></i>
                                <h5 class="card-title mt-3"><?= e($cat['name'] ?? $cat['slug']) ?></h5>
                                <p class="card-text text-muted small"><?= e($cat['description'] ?? '') ?></p>
                                <span class="badge bg-secondary"><?= (int)($cat['entry_count'] ?? 0) ?> <?= e(__('entries')) ?></span>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Recent Entries -->
<?php if (!empty($recentEntries)): ?>
<section class="py-5" style="background-color: var(--bs-tertiary-bg);">
    <div class="container">
        <h2 class="mb-4"><i class="bi bi-clock-history me-2"></i><?= e(__('recent_entries')) ?></h2>
        <div class="row g-4">
            <?php foreach ($recentEntries as $entry): ?>
                <div class="col-md-6 col-lg-3">
                    <div class="card h-100 shadow-sm border-0">
                        <?php $thumb = json_decode($entry['images_json'] ?? '[]', true)[0] ?? ($entry['image'] ?? ''); ?>
                        <?php if (!empty($thumb)): ?>
                            <img src="<?= e($thumb) ?>" class="card-img-top" alt="<?= e($entry['title'] ?? '') ?>"
                                 style="height: 160px; object-fit: cover;">
                        <?php else: ?>
                            <div class="card-img-top bg-secondary d-flex align-items-center justify-content-center"
                                 style="height: 160px;">
                                <i class="bi bi-image text-white display-6"></i>
                            </div>
                        <?php endif; ?>
                        <div class="card-body">
                            <h6 class="card-title"><?= e($entry['title'] ?? $entry['slug']) ?></h6>
                            <?php if (!empty($entry['summary'])): ?>
                                <p class="card-text small text-muted"><?= e(mb_strimwidth($entry['summary'], 0, 100, '…')) ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="card-footer bg-transparent border-0">
                            <a href="/<?= e($entry['category_slug']) ?>/<?= e($entry['slug']) ?>"
                               class="btn btn-sm btn-outline-primary"><?= e(__('more')) ?> &rarr;</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>
