<?php
/**
 * Single file resource detail (texture, sound, music).
 *
 * Variables: $category, $item
 */
$isImage = $item['type'] === 'image';
$isAudio = $item['type'] === 'audio';
?>
<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><?= e(__('home')) ?></a></li>
            <li class="breadcrumb-item">
                <a href="/<?= e($category['slug']) ?>"><?= e($category['name'] ?? $category['slug']) ?></a>
            </li>
            <li class="breadcrumb-item active"><?= e($item['name']) ?></li>
        </ol>
    </nav>

    <div class="row">
        <!-- Main content -->
        <div class="col-lg-8">
            <h1 class="mb-3"><?= e($item['name']) ?></h1>

            <?php if ($isImage): ?>
                <!-- Texture preview -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body bg-dark text-center p-4" style="min-height: 300px;">
                        <img src="<?= e($item['url']) ?>" alt="<?= e($item['name']) ?>"
                             class="img-fluid" style="max-height: 500px; image-rendering: pixelated;"
                             onerror="this.style.display='none'; this.parentElement.innerHTML='<div class=\'text-secondary py-5\'><i class=\'bi bi-exclamation-triangle display-3\'></i><p class=\'mt-2\'><?= e(__('file_load_error')) ?></p></div>';">
                    </div>
                </div>

                <!-- Checkerboard preview for transparency -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header"><?= e(__('transparency_preview')) ?></div>
                    <div class="card-body text-center p-4" style="background: repeating-conic-gradient(#ccc 0% 25%, #fff 0% 50%) 50% / 20px 20px;">
                        <img src="<?= e($item['url']) ?>" alt="<?= e($item['name']) ?>"
                             class="img-fluid" style="max-height: 400px;"
                             loading="lazy">
                    </div>
                </div>

            <?php elseif ($isAudio): ?>
                <!-- Audio player -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body text-center py-5">
                        <i class="bi bi-<?= $category['slug'] === 'music' ? 'music-note-beamed' : 'volume-up-fill' ?> display-1 text-primary mb-3"></i>
                        <div class="mx-auto" style="max-width: 500px;">
                            <audio controls class="w-100" preload="metadata">
                                <source src="<?= e($item['url']) ?>" type="audio/ogg">
                                <?= e(__('audio_not_supported')) ?>
                            </audio>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar: file info -->
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-primary text-white">
                    <i class="bi bi-info-circle me-1"></i><?= e(__('details')) ?>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between">
                        <strong><?= e(__('file_name')) ?></strong>
                        <span class="text-end text-break" style="max-width: 60%;"><?= e($item['filename']) ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <strong><?= e(__('file_id')) ?></strong>
                        <span>#<?= $item['file_id'] ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <strong><?= e(__('file_type')) ?></strong>
                        <span class="text-uppercase"><?= e($item['extension']) ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <strong><?= e(__('category')) ?></strong>
                        <a href="/<?= e($category['slug']) ?>"><?= e($category['name'] ?? $category['slug']) ?></a>
                    </li>
                </ul>
            </div>

            <!-- Download -->
            <div class="d-grid mb-4">
                <a href="<?= e($item['url']) ?>" class="btn btn-primary" target="_blank" rel="noopener" download>
                    <i class="bi bi-download me-1"></i><?= e(__('download')) ?>
                </a>
            </div>

            <!-- Direct link -->
            <div class="card shadow-sm border-0">
                <div class="card-header"><?= e(__('direct_link')) ?></div>
                <div class="card-body">
                    <div class="input-group input-group-sm">
                        <input type="text" class="form-control font-monospace" value="<?= e($item['url']) ?>" readonly id="directLinkInput">
                        <button class="btn btn-outline-secondary" type="button" onclick="navigator.clipboard.writeText(document.getElementById('directLinkInput').value).then(()=>this.innerHTML='<i class=\'bi bi-check\'></i>')">
                            <i class="bi bi-clipboard"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
