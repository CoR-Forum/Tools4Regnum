<?php
/**
 * File-based resource category listing (textures, sounds, music).
 *
 * Variables: $category, $items, $pagination, $query, $lastUpdate
 */
$isTextures = $category['slug'] === 'textures';
$isAudio    = in_array($category['slug'], ['sounds', 'music']);
?>
<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><?= e(__('home')) ?></a></li>
            <li class="breadcrumb-item active"><?= e($category['name'] ?? $category['slug']) ?></li>
        </ol>
    </nav>

    <div class="d-flex align-items-center mb-3">
        <i class="bi <?= e($category['icon'] ?? 'bi-folder') ?> display-5 text-primary me-3"></i>
        <div>
            <h1 class="mb-0"><?= e($category['name'] ?? $category['slug']) ?></h1>
            <?php if (!empty($category['description'])): ?>
                <p class="text-muted mb-0"><?= e($category['description']) ?></p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Stats & search -->
    <div class="d-flex flex-wrap align-items-center gap-3 mb-4">
        <span class="badge bg-secondary fs-6">
            <?= number_format($pagination['total']) ?> <?= e(__('entries')) ?>
        </span>
        <?php if ($lastUpdate): ?>
            <small class="text-muted">
                <i class="bi bi-clock me-1"></i><?= e(__('last_update')) ?>: <?= e($lastUpdate) ?>
            </small>
        <?php endif; ?>

        <form action="/<?= e($category['slug']) ?>" method="GET" class="ms-auto" style="max-width: 320px; width: 100%;">
            <div class="input-group input-group-sm">
                <input type="search" class="form-control file-filter-input" name="q"
                       value="<?= e($query) ?>"
                       placeholder="<?= e(__('filter_placeholder')) ?>"
                       data-category="<?= e($category['slug']) ?>"
                       autocomplete="off">
                <button class="btn btn-outline-primary" type="submit">
                    <i class="bi bi-search"></i>
                </button>
                <?php if ($query !== ''): ?>
                    <a href="/<?= e($category['slug']) ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-x-lg"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <?php if (empty($items)): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle me-2"></i>
            <?php if ($query !== ''): ?>
                <?= e(__('search_no_results', ['query' => $query])) ?>
            <?php else: ?>
                <?= e(__('no_entries')) ?>
            <?php endif; ?>
        </div>
    <?php else: ?>

        <?php if ($isTextures): ?>
            <!-- Texture grid (image thumbnails) -->
            <div class="row g-3 infinite-scroll-container"
                 data-infinite-url="/api/files/<?= e($category['slug']) ?>"
                 data-infinite-page="<?= $pagination['page'] ?>"
                 data-infinite-total-pages="<?= $pagination['totalPages'] ?>"
                 data-infinite-type="textures"
                 data-infinite-query="<?= e($query) ?>"
                 data-category-slug="<?= e($category['slug']) ?>">
                <?php foreach ($items as $item): ?>
                    <div class="col-6 col-sm-4 col-md-3 col-lg-2">
                        <a href="/<?= e($category['slug']) ?>/<?= e($item['slug']) ?>" class="text-decoration-none">
                            <div class="card h-100 shadow-sm border-0 entry-card text-center">
                                <div class="card-img-top bg-dark d-flex align-items-center justify-content-center"
                                     style="height: 120px; overflow: hidden;">
                                    <img src="<?= e($item['url']) ?>" alt="<?= e($item['name']) ?>"
                                         style="max-width: 100%; max-height: 120px; object-fit: contain;"
                                         loading="lazy"
                                         onerror="this.style.display='none'; this.parentElement.innerHTML='<i class=\'bi bi-image text-secondary display-6\'></i>';">
                                </div>
                                <div class="card-body p-2">
                                    <small class="text-body-emphasis d-block text-truncate" title="<?= e($item['name']) ?>">
                                        <?= e($item['name']) ?>
                                    </small>
                                    <small class="text-muted">#<?= $item['file_id'] ?></small>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php elseif ($isAudio): ?>
            <!-- Audio list -->
            <div class="list-group infinite-scroll-container"
                 data-infinite-url="/api/files/<?= e($category['slug']) ?>"
                 data-infinite-page="<?= $pagination['page'] ?>"
                 data-infinite-total-pages="<?= $pagination['totalPages'] ?>"
                 data-infinite-type="audio"
                 data-infinite-query="<?= e($query) ?>"
                 data-category-slug="<?= e($category['slug']) ?>"
                 data-audio-icon="<?= $category['slug'] === 'music' ? 'music-note-beamed' : 'volume-up' ?>">
                <?php foreach ($items as $item): ?>
                    <div class="list-group-item d-flex align-items-center gap-3">
                        <a href="/<?= e($category['slug']) ?>/<?= e($item['slug']) ?>"
                           class="text-decoration-none text-primary flex-shrink-0">
                            <i class="bi bi-<?= $category['slug'] === 'music' ? 'music-note-beamed' : 'volume-up' ?> fs-4"></i>
                        </a>
                        <div class="flex-grow-1 min-w-0">
                            <a href="/<?= e($category['slug']) ?>/<?= e($item['slug']) ?>"
                               class="text-decoration-none">
                                <strong class="d-block text-truncate"><?= e($item['name']) ?></strong>
                            </a>
                            <small class="text-muted">#<?= $item['file_id'] ?> &middot; <?= e($item['extension']) ?></small>
                        </div>
                        <audio controls preload="none" class="flex-shrink-0" style="max-width: 250px; height: 32px;">
                            <source src="<?= e($item['url']) ?>" type="audio/ogg">
                        </audio>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

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
                    $qs = $query !== '' ? '&q=' . urlencode($query) : '';
                    if ($p > 1): ?>
                        <li class="page-item"><a class="page-link" href="?page=<?= $p - 1 ?><?= $qs ?>">&laquo;</a></li>
                    <?php endif;
                    $start = max(1, $p - 5);
                    $end = min($tp, $p + 5);
                    if ($start > 1): ?>
                        <li class="page-item"><a class="page-link" href="?page=1<?= $qs ?>">1</a></li>
                        <?php if ($start > 2): ?><li class="page-item disabled"><span class="page-link">&hellip;</span></li><?php endif;
                    endif;
                    for ($i = $start; $i <= $end; $i++): ?>
                        <li class="page-item <?= $i === $p ? 'active' : '' ?>"><a class="page-link" href="?page=<?= $i ?><?= $qs ?>"><?= $i ?></a></li>
                    <?php endfor;
                    if ($end < $tp): ?>
                        <?php if ($end < $tp - 1): ?><li class="page-item disabled"><span class="page-link">&hellip;</span></li><?php endif; ?>
                        <li class="page-item"><a class="page-link" href="?page=<?= $tp ?><?= $qs ?>"><?= $tp ?></a></li>
                    <?php endif;
                    if ($p < $tp): ?>
                        <li class="page-item"><a class="page-link" href="?page=<?= $p + 1 ?><?= $qs ?>">&raquo;</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>

    <?php endif; ?>
</div>
