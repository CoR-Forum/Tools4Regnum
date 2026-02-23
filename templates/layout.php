<?php
/**
 * Main layout template — Bootstrap 5 shell.
 *
 * Variables:
 *   $_page_title      — Page title
 *   $_content_template — Template to render as body content
 *   (plus any extra data vars)
 */

use App\Auth;

$_appName  = __('app_name');
$_title    = !empty($_page_title) ? e($_page_title) . ' — ' . $_appName : $_appName;
$_lang     = currentLang();
$_langs    = availableLanguages();
$_user     = Auth::currentUser();
$_isAdmin  = Auth::isAdmin();
$_flash    = getFlash();
$_categories = \App\models\Category::all();
?>
<!DOCTYPE html>
<html lang="<?= e($_lang) ?>" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $_title ?></title>
    <meta name="description" content="<?= e(__('app_subtitle')) ?>">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="/assets/css/style.css" rel="stylesheet">

    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-1LR997BKEZ"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());
      gtag('config', 'G-1LR997BKEZ');
    </script>
</head>
<body class="d-flex flex-column min-vh-100">

<!-- CoRT Notice -->
<div class="bg-primary text-white text-center py-1 small">
    Looking for CoRT? Visit <a href="https://cort.ovh?utm_source=tools4regnum" class="text-white fw-bold text-decoration-underline" target="_blank" rel="noopener">cort.ovh</a>
</div>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold" href="/">
            <i class="bi bi-shield-fill me-1"></i><?= e($_appName) ?>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMain">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="/"><i class="bi bi-house-fill me-1"></i><?= e(__('home')) ?></a>
                </li>
                <!-- Categories dropdown -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                        <i class="bi bi-grid-fill me-1"></i><?= e(__('categories')) ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-dark">
                        <?php foreach ($_categories as $cat): ?>
                            <li>
                                <a class="dropdown-item" href="/<?= e($cat['slug']) ?>">
                                    <i class="bi <?= e($cat['icon'] ?? 'bi-folder') ?> me-2"></i><?= e($cat['name'] ?? $cat['slug']) ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </li>
            </ul>

            <!-- Search (live) -->
            <form class="d-flex me-3 live-search-wrapper" action="/search" method="GET" role="search">
                <div class="input-group input-group-sm">
                    <input type="search" class="form-control live-search-input" name="q"
                           placeholder="<?= e(__('search_placeholder')) ?>"
                           value="<?= e($_GET['q'] ?? '') ?>"
                           autocomplete="off">
                    <button class="btn btn-outline-light" type="submit">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
                <div class="live-search-dropdown"></div>
            </form>

            <!-- Right side -->
            <ul class="navbar-nav">
                <!-- Language switcher -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                        <?= $_langs[$_lang]['flag'] ?? '' ?> <?= strtoupper($_lang) ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-dark dropdown-menu-end">
                        <?php foreach ($_langs as $code => $info): ?>
                            <li>
                                <a class="dropdown-item <?= $code === $_lang ? 'active' : '' ?>"
                                   href="/lang/<?= e($code) ?>">
                                    <?= $info['flag'] ?> <?= e($info['name']) ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </li>

                <?php if ($_user): ?>
                    <!-- Favorites -->
                    <li class="nav-item">
                        <a class="nav-link" href="/favorites">
                            <i class="bi bi-heart-fill me-1"></i><?= e(__('favorites')) ?>
                        </a>
                    </li>
                    <?php if ($_isAdmin): ?>
                        <li class="nav-item">
                            <a class="nav-link text-warning" href="/admin">
                                <i class="bi bi-gear-fill me-1"></i><?= e(__('admin')) ?>
                            </a>
                        </li>
                    <?php endif; ?>
                    <!-- User dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle me-1"></i><?= e($_user['username']) ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-dark dropdown-menu-end">
                            <li><a class="dropdown-item" href="/favorites"><i class="bi bi-heart me-2"></i><?= e(__('favorites')) ?></a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="/logout"><i class="bi bi-box-arrow-right me-2"></i><?= e(__('logout')) ?></a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="/login">
                            <i class="bi bi-box-arrow-in-right me-1"></i><?= e(__('login')) ?>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- Flash messages -->
<?php if (!empty($_flash)): ?>
<div class="container mt-3">
    <?php foreach ($_flash as $type => $messages): ?>
        <?php foreach ($messages as $msg): ?>
            <div class="alert alert-<?= e($type) ?> alert-dismissible fade show" role="alert">
                <?= e($msg) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endforeach; ?>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Main content -->
<main class="flex-grow-1">
    <?php
    if (!empty($_content_template)) {
        render($_content_template, get_defined_vars());
    }
    ?>
</main>

<!-- Footer -->
<footer class="bg-dark text-light py-4 mt-auto">
    <div class="container">
        <div class="row">
            <div class="col-md-6">
                <h6 class="fw-bold"><i class="bi bi-shield-fill me-1"></i><?= e($_appName) ?></h6>
                <p class="small text-secondary mb-0"><?= e(__('footer_text')) ?></p>
            </div>
            <div class="col-md-6 text-md-end">
                <a href="https://cor-forum.de" class="text-secondary text-decoration-none" target="_blank" rel="noopener">
                    <i class="bi bi-chat-dots me-1"></i><?= e(__('footer_forum')) ?>
                </a>
            </div>
        </div>
    </div>
</footer>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
<!-- App JS -->
<script>
    const CSRF_TOKEN = "<?= e(csrfToken()) ?>";
    const IS_LOGGED_IN = <?= Auth::isLoggedIn() ? 'true' : 'false' ?>;
</script>
<script src="/assets/js/app.js"></script>
</body>
</html>
