<?php
/**
 * Search results template.
 *
 * Variables: $query, $dbResults, $fileResults
 */
$totalResults = count($dbResults) + count($fileResults);
?>
<div class="container py-4">
    <h1><i class="bi bi-search me-2"></i><?= e(__('search_results')) ?></h1>

    <?php if ($query !== ''): ?>
        <p class="text-muted"><?= e(__('search_results_for', ['query' => $query])) ?></p>
    <?php endif; ?>

    <!-- Search form (live search enabled) -->
    <form action="/search" method="GET" class="mb-4" id="searchPageForm">
        <div class="input-group">
            <input type="search" class="form-control" name="q" id="searchPageInput"
                   value="<?= e($query) ?>"
                   placeholder="<?= e(__('search_placeholder')) ?>"
                   autocomplete="off">
            <button class="btn btn-primary" type="submit">
                <i class="bi bi-search"></i> <?= e(__('search')) ?>
            </button>
        </div>
    </form>

    <div id="searchPageResults">
        <?php if ($totalResults === 0 && $query !== ''): ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle me-2"></i><?= e(__('search_no_results', ['query' => $query])) ?>
            </div>

        <?php else: ?>
            <?php if (!empty($dbResults)): ?>
                <h5 class="mb-3"><i class="bi bi-journal-text me-2"></i><?= e(__('wiki_entries')) ?> <span class="badge bg-secondary"><?= count($dbResults) ?></span></h5>
                <div class="list-group mb-4">
                    <?php foreach ($dbResults as $result): ?>
                        <a href="/<?= e($result['category_slug']) ?>/<?= e($result['slug']) ?>"
                           class="list-group-item list-group-item-action">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="mb-1"><?= e($result['title']) ?></h6>
                                    <?php if (!empty($result['snippet'])): ?>
                                        <p class="mb-1 small"><?= $result['snippet'] ?></p>
                                    <?php elseif (!empty($result['summary'])): ?>
                                        <p class="mb-1 small text-muted"><?= e($result['summary']) ?></p>
                                    <?php endif; ?>
                                </div>
                                <span class="badge bg-primary"><?= e($result['category_slug']) ?></span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($fileResults)): ?>
                <h5 class="mb-3"><i class="bi bi-file-earmark me-2"></i><?= e(__('file_resources')) ?> <span class="badge bg-secondary"><?= count($fileResults) ?></span></h5>
                <div class="row g-3">
                    <?php foreach (array_slice($fileResults, 0, 60) as $file): ?>
                        <div class="col-6 col-sm-4 col-md-3 col-lg-2">
                            <a href="/<?= e($file['category_slug']) ?>/<?= e($file['slug']) ?>" class="text-decoration-none">
                                <div class="card h-100 shadow-sm border-0 entry-card text-center">
                                    <?php if ($file['type'] === 'image'): ?>
                                        <div class="card-img-top bg-dark d-flex align-items-center justify-content-center"
                                             style="height: 100px; overflow: hidden;">
                                            <img src="<?= e($file['url']) ?>" alt="<?= e($file['name']) ?>"
                                                 style="max-width: 100%; max-height: 100px; object-fit: contain;"
                                                 loading="lazy"
                                                 onerror="this.style.display='none'; this.parentElement.innerHTML='<i class=\'bi bi-image text-secondary display-6\'></i>';">
                                        </div>
                                    <?php else: ?>
                                        <div class="card-img-top bg-dark d-flex align-items-center justify-content-center"
                                             style="height: 100px;">
                                            <i class="bi bi-<?= $file['category_slug'] === 'music' ? 'music-note-beamed' : 'volume-up' ?> text-secondary display-6"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div class="card-body p-2">
                                        <small class="text-body-emphasis d-block text-truncate"><?= e($file['name']) ?></small>
                                        <small class="text-muted"><?= e($file['category_slug']) ?></small>
                                    </div>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                    <?php if (count($fileResults) > 60): ?>
                        <div class="col-12">
                            <div class="alert alert-info mt-2">
                                <i class="bi bi-info-circle me-2"></i>
                                <?= count($fileResults) - 60 ?> more results not shown. Use category filters for more specific results.
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
