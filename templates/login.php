<?php
/**
 * Login page template.
 */
?>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow border-0">
                <div class="card-body p-4">
                    <h3 class="card-title text-center mb-1">
                        <i class="bi bi-box-arrow-in-right me-2"></i><?= e(__('login_title')) ?>
                    </h3>
                    <p class="text-muted text-center small mb-4"><?= e(__('login_description')) ?></p>

                    <form method="POST" action="/login">
                        <?php csrfField(); ?>

                        <div class="mb-3">
                            <label for="username" class="form-label"><?= e(__('username')) ?></label>
                            <input type="text" class="form-control" id="username" name="username"
                                   required autofocus autocomplete="username">
                        </div>

                        <div class="mb-4">
                            <label for="password" class="form-label"><?= e(__('password')) ?></label>
                            <input type="password" class="form-control" id="password" name="password"
                                   required autocomplete="current-password">
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-box-arrow-in-right me-1"></i><?= e(__('login')) ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
