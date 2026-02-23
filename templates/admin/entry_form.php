<?php
/**
 * Admin entry create/edit form.
 *
 * Variables: $entry (null=create), $translations, $categories, $formAction, $formTitle
 */
$isEdit = $entry !== null;
$langs  = ['de' => 'Deutsch', 'en' => 'English', 'es' => 'Español'];
?>
<div class="container py-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/admin"><?= e(__('admin')) ?></a></li>
            <li class="breadcrumb-item active"><?= e($formTitle) ?></li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="bi bi-<?= $isEdit ? 'pencil' : 'plus-circle' ?> me-2"></i><?= e($formTitle) ?></h1>
        <?php if ($isEdit): ?>
            <div class="d-flex gap-2">
                <a href="/<?= e($entry['category_slug'] ?? '') ?>/<?= e($entry['slug']) ?>"
                   class="btn btn-outline-info btn-sm" target="_blank">
                    <i class="bi bi-eye me-1"></i>View
                </a>
                <form method="POST" action="/admin/entry/<?= (int)$entry['id'] ?>/delete"
                      onsubmit="return confirm('<?= e(__('delete_confirm')) ?>')">
                    <?php csrfField(); ?>
                    <button type="submit" class="btn btn-outline-danger btn-sm">
                        <i class="bi bi-trash me-1"></i><?= e(__('delete_entry')) ?>
                    </button>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <form method="POST" action="<?= e($formAction) ?>" enctype="multipart/form-data">
        <?php csrfField(); ?>

        <div class="row">
            <!-- Left column: main fields -->
            <div class="col-lg-8">
                <!-- Translation tabs -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header">
                        <ul class="nav nav-tabs card-header-tabs" role="tablist">
                            <?php $first = true; foreach ($langs as $code => $name): ?>
                                <li class="nav-item">
                                    <button class="nav-link <?= $first ? 'active' : '' ?>"
                                            data-bs-toggle="tab" data-bs-target="#lang-<?= $code ?>"
                                            type="button"><?= e($name) ?></button>
                                </li>
                            <?php $first = false; endforeach; ?>
                        </ul>
                    </div>
                    <div class="card-body">
                        <div class="tab-content">
                            <?php $first = true; foreach ($langs as $code => $name): ?>
                                <?php $t = $translations[$code] ?? []; ?>
                                <div class="tab-pane fade <?= $first ? 'show active' : '' ?>" id="lang-<?= $code ?>">
                                    <div class="mb-3">
                                        <label class="form-label"><?= e(__('title')) ?> (<?= strtoupper($code) ?>)
                                            <?= $code === 'de' ? '<span class="text-danger">*</span>' : '' ?>
                                        </label>
                                        <input type="text" class="form-control" name="title_<?= $code ?>"
                                               value="<?= e($t['title'] ?? '') ?>"
                                               <?= $code === 'de' ? 'required' : '' ?>>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label"><?= e(__('summary')) ?> (<?= strtoupper($code) ?>)</label>
                                        <textarea class="form-control" name="summary_<?= $code ?>"
                                                  rows="2"><?= e($t['summary'] ?? '') ?></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label"><?= e(__('body')) ?> (<?= strtoupper($code) ?>)</label>
                                        <textarea class="form-control" name="body_<?= $code ?>"
                                                  rows="10"><?= e($t['body'] ?? '') ?></textarea>
                                    </div>
                                </div>
                            <?php $first = false; endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right column: meta fields -->
            <div class="col-lg-4">
                <!-- Category & Slug -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header"><?= e(__('details')) ?></div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label"><?= e(__('category')) ?> <span class="text-danger">*</span></label>
                            <select class="form-select" name="category_id" required>
                                <option value="">—</option>
                                <?php foreach ($categories as $cat): ?>
                                    <?php if (\App\models\FileResource::isFileCategory($cat['slug'])) continue; ?>
                                    <option value="<?= (int)$cat['id'] ?>"
                                            <?= ($isEdit && $entry['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                                        <?= e($cat['name'] ?? $cat['slug']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?= e(__('slug')) ?></label>
                            <input type="text" class="form-control" name="slug"
                                   value="<?= e($isEdit ? $entry['slug'] : '') ?>"
                                   placeholder="auto-generated if empty">
                        </div>
                    </div>
                </div>

                <!-- Images -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header"><?= e(__('images')) ?></div>
                    <div class="card-body">
                        <?php
                        $existingImages = [];
                        if ($isEdit) {
                            $existingImages = json_decode($entry['images_json'] ?? '[]', true) ?: [];
                        }
                        ?>
                        <?php if (!empty($existingImages)): ?>
                            <div class="row g-2 mb-3">
                                <?php foreach ($existingImages as $img): ?>
                                    <div class="col-6">
                                        <div class="position-relative">
                                            <img src="<?= e($img) ?>" class="img-fluid rounded" alt="">
                                            <div class="form-check position-absolute top-0 end-0 m-1">
                                                <input type="checkbox" class="form-check-input bg-danger border-danger"
                                                       name="delete_images[]" value="<?= e($img) ?>" id="del-<?= md5($img) ?>">
                                                <label class="form-check-label text-danger small fw-bold"
                                                       for="del-<?= md5($img) ?>"><i class="bi bi-trash"></i></label>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <small class="text-muted d-block mb-2"><?= e(__('delete_images_hint')) ?></small>
                        <?php endif; ?>
                        <input type="file" class="form-control" name="images[]" accept="image/*" multiple>
                        <small class="text-muted"><?= e(__('upload_multiple_hint')) ?></small>
                    </div>
                </div>

                <!-- Data JSON -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header"><?= e(__('data_json')) ?></div>
                    <div class="card-body">
                        <textarea class="form-control font-monospace" name="data_json"
                                  rows="6" placeholder='{"hp": 100, "level": 5}'><?= e($isEdit ? ($entry['data_json'] ?? '{}') : '{}') ?></textarea>
                        <small class="text-muted">JSON key-value pairs for category-specific attributes.</small>
                    </div>
                </div>

                <!-- Submit -->
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="bi bi-check-circle me-1"></i><?= e(__('save')) ?>
                    </button>
                    <a href="/admin" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle me-1"></i><?= e(__('cancel')) ?>
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>
