<?php
/**
 * Single entry detail template.
 *
 * Variables: $entry, $category, $isFavorited
 */
?>
<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><?= e(__('home')) ?></a></li>
            <li class="breadcrumb-item">
                <a href="/<?= e($category['slug']) ?>"><?= e($category['name'] ?? $category['slug']) ?></a>
            </li>
            <li class="breadcrumb-item active"><?= e($entry['title'] ?? $entry['slug']) ?></li>
        </ol>
    </nav>

    <div class="row">
        <!-- Main content -->
        <div class="col-lg-8">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <h1 class="mb-0"><?= e($entry['title'] ?? $entry['slug']) ?></h1>
                <div class="d-flex align-items-center gap-2">
                    <?php if (\App\Auth::isLoggedIn()): ?>
                        <button class="btn btn-outline-danger favorite-btn"
                                data-entry-id="<?= (int)$entry['id'] ?>"
                                title="<?= $isFavorited ? e(__('remove_favorite')) : e(__('add_favorite')) ?>">
                            <i class="bi <?= $isFavorited ? 'bi-heart-fill' : 'bi-heart' ?>"></i>
                            <span class="d-none d-md-inline ms-1">
                                <?= $isFavorited ? e(__('remove_favorite')) : e(__('add_favorite')) ?>
                            </span>
                        </button>
                    <?php endif; ?>
                    <?php if (\App\Auth::isAdmin()): ?>
                        <a href="/admin/entry/<?= (int)$entry['id'] ?>/edit" class="btn btn-outline-warning btn-sm">
                            <i class="bi bi-pencil"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!empty($entry['summary'])): ?>
                <p class="lead text-muted"><?= e($entry['summary']) ?></p>
            <?php endif; ?>

            <?php
            $images = $entry['images'] ?? [];
            if (empty($images) && !empty($entry['image'])) {
                $images = [$entry['image']]; // Legacy single-image fallback
            }
            ?>
            <?php if (!empty($images)): ?>
                <?php if (count($images) === 1): ?>
                    <img src="<?= e($images[0]) ?>" alt="<?= e($entry['title'] ?? '') ?>"
                         class="img-fluid rounded shadow-sm mb-4" style="max-height: 400px; object-fit: cover; width: 100%;">
                <?php else: ?>
                    <!-- Image carousel -->
                    <div id="entryCarousel" class="carousel slide mb-4 shadow-sm rounded overflow-hidden" data-bs-ride="carousel">
                        <div class="carousel-indicators">
                            <?php foreach ($images as $i => $img): ?>
                                <button type="button" data-bs-target="#entryCarousel" data-bs-slide-to="<?= $i ?>"
                                        <?= $i === 0 ? 'class="active"' : '' ?>></button>
                            <?php endforeach; ?>
                        </div>
                        <div class="carousel-inner">
                            <?php foreach ($images as $i => $img): ?>
                                <div class="carousel-item <?= $i === 0 ? 'active' : '' ?>">
                                    <img src="<?= e($img) ?>" class="d-block w-100"
                                         alt="<?= e($entry['title'] ?? '') ?> (<?= $i + 1 ?>)"
                                         style="max-height: 400px; object-fit: cover;">
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <button class="carousel-control-prev" type="button" data-bs-target="#entryCarousel" data-bs-slide="prev">
                            <span class="carousel-control-prev-icon"></span>
                        </button>
                        <button class="carousel-control-next" type="button" data-bs-target="#entryCarousel" data-bs-slide="next">
                            <span class="carousel-control-next-icon"></span>
                        </button>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <?php if (!empty($entry['body'])): ?>
                <div class="entry-body mt-3">
                    <?= nl2br(e($entry['body'])) ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Attributes / Data -->
            <?php if (!empty($entry['data'])): ?>
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-primary text-white">
                        <i class="bi bi-list-ul me-1"></i><?= e(__('attributes')) ?>
                    </div>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($entry['data'] as $key => $value): ?>
                            <li class="list-group-item d-flex justify-content-between">
                                <strong><?= e(ucfirst(str_replace('_', ' ', $key))) ?></strong>
                                <span><?= e(is_array($value) ? implode(', ', $value) : (string)$value) ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- Meta info -->
            <div class="card shadow-sm border-0">
                <div class="card-header bg-secondary text-white">
                    <i class="bi bi-info-circle me-1"></i><?= e(__('details')) ?>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between">
                        <span><?= e(__('category')) ?></span>
                        <a href="/<?= e($category['slug']) ?>"><?= e($category['name'] ?? $category['slug']) ?></a>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span><?= e(__('created_at')) ?></span>
                        <span><?= e($entry['created_at'] ?? '—') ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span><?= e(__('updated_at')) ?></span>
                        <span><?= e($entry['updated_at'] ?? '—') ?></span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
