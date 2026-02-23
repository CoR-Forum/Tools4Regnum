<?php
/**
 * Global helper functions.
 */

/**
 * HTML-escape a string.
 */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Render a template with variables.
 */
function render(string $template, array $data = []): void
{
    extract($data, EXTR_SKIP);
    $__templateFile = TEMPLATE_PATH . '/' . $template . '.php';
    if (!file_exists($__templateFile)) {
        echo "Template not found: " . e($template);
        return;
    }
    require $__templateFile;
}

/**
 * Render a template inside the main layout.
 */
function renderWithLayout(string $template, array $data = [], string $pageTitle = ''): void
{
    $data['_content_template'] = $template;
    $data['_page_title'] = $pageTitle;
    render('layout', $data);
}

/**
 * Generate a URL path.
 */
function url(string $path = '/'): string
{
    return rtrim($path, '/') ?: '/';
}

/**
 * Redirect to a URL.
 */
function redirect(string $path, int $code = 302): void
{
    header('Location: ' . url($path), true, $code);
    exit;
}

/**
 * Get CSRF token.
 */
function csrfToken(): string
{
    return $_SESSION['csrf_token'] ?? '';
}

/**
 * Output a hidden CSRF input field.
 */
function csrfField(): void
{
    echo '<input type="hidden" name="_csrf" value="' . e(csrfToken()) . '">';
}

/**
 * Validate CSRF token from POST request.
 */
function verifyCsrf(): bool
{
    $token = $_POST['_csrf'] ?? '';
    return hash_equals(csrfToken(), $token);
}

/**
 * Require CSRF validation — aborts with 403 on failure.
 */
function requireCsrf(): void
{
    if (!verifyCsrf()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Invalid CSRF token.']);
        exit;
    }
}

/**
 * Require logged-in user. Redirects to login page if not.
 */
function requireAuth(): void
{
    if (!\App\Auth::isLoggedIn()) {
        redirect('/login');
    }
}

/**
 * Require admin access. Shows 403 if not admin.
 */
function requireAdmin(): void
{
    requireAuth();
    if (!\App\Auth::isAdmin()) {
        http_response_code(403);
        renderWithLayout('errors/403');
        exit;
    }
}

/**
 * Create a URL-safe slug from a string.
 */
function slugify(string $text): string
{
    if (function_exists('transliterator_transliterate')) {
        $text = transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $text);
    } else {
        // Fallback: manual German transliteration + lowercase
        $map = ['ä'=>'ae','ö'=>'oe','ü'=>'ue','Ä'=>'Ae','Ö'=>'Oe','Ü'=>'Ue','ß'=>'ss',
                'á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ñ'=>'n',
                'à'=>'a','è'=>'e','ì'=>'i','ò'=>'o','ù'=>'u','ê'=>'e','â'=>'a','î'=>'i','ô'=>'o','û'=>'u'];
        $text = strtr($text, $map);
        $text = strtolower($text);
    }
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim($text, '-');
}

/**
 * Set a flash message in the session.
 */
function flash(string $type, string $message): void
{
    $_SESSION['flash'][$type][] = $message;
}

/**
 * Get and clear flash messages.
 */
function getFlash(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

/**
 * Simple pagination helper.
 *
 * @return array{offset: int, limit: int, page: int, totalPages: int}
 */
function paginate(int $total, int $perPage = 24, string $paramName = 'page'): array
{
    $page = max(1, intval($_GET[$paramName] ?? 1));
    $totalPages = max(1, (int)ceil($total / $perPage));
    $page = min($page, $totalPages);
    return [
        'offset'     => ($page - 1) * $perPage,
        'limit'      => $perPage,
        'page'       => $page,
        'totalPages' => $totalPages,
    ];
}

/**
 * Return JSON response and exit.
 */
function jsonResponse(array $data, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Get uploaded file or null.
 */
function getUploadedFile(string $name): ?array
{
    if (isset($_FILES[$name]) && $_FILES[$name]['error'] === UPLOAD_ERR_OK) {
        return $_FILES[$name];
    }
    return null;
}
