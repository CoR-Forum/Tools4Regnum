<?php
/**
 * 404 error page.
 */
?>
<div class="container py-5 text-center">
    <i class="bi bi-exclamation-triangle display-1 text-warning"></i>
    <h1 class="mt-3"><?= e(__('error_404')) ?></h1>
    <p class="text-muted"><?= e(__('error_404_text')) ?></p>
    <a href="/" class="btn btn-primary mt-3">
        <i class="bi bi-house me-1"></i><?= e(__('home')) ?>
    </a>
</div>
