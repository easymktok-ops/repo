<?php
declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = '/'): string
{
    return APP_URL . '/' . ltrim($path, '/');
}

function asset(string $path): string
{
    $file = PUBLIC_DIR . '/assets/' . ltrim($path, '/');
    $version = is_file($file) ? (string) filemtime($file) : '0';
    return '/assets/' . ltrim($path, '/') . '?v=' . $version;
}

function asset_exists(string $path): bool
{
    return is_file(PUBLIC_DIR . '/assets/' . ltrim($path, '/'));
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function csrf_valid(?string $token): bool
{
    return is_string($token) && !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $token);
}

function format_cop(int $amount): string
{
    return '$' . number_format($amount, 0, ',', '.') . ' COP';
}

function whatsapp_link(string $number, string $message = ''): string
{
    $digits = preg_replace('/\D+/', '', $number) ?? '';
    if ($digits === '') {
        return '#contacto';
    }
    $query = $message !== '' ? '?text=' . rawurlencode($message) : '';
    return 'https://wa.me/' . $digits . $query;
}

function view(string $name, array $data = []): void
{
    extract($data, EXTR_SKIP);
    require APP_DIR . '/views/' . $name . '.php';
}

function partial(string $name, array $data = []): void
{
    view('partials/' . $name, $data);
}

/**
 * Imagen responsiva si el archivo existe en public/assets/img/, si no un
 * placeholder con el nombre esperado para que Norman sepa qué subir.
 */
function media(string $file, string $alt, int $width, int $height, string $class = '', bool $eager = false): string
{
    $classes = trim('media ' . $class);
    if (!asset_exists('img/' . $file)) {
        return sprintf(
            '<div class="%s media--placeholder" style="aspect-ratio:%d/%d" role="img" aria-label="%s"><span>%s</span></div>',
            e($classes),
            $width,
            $height,
            e($alt),
            e('Falta: assets/img/' . $file)
        );
    }

    $base = pathinfo($file, PATHINFO_FILENAME);
    $sources = '';
    foreach (['avif' => 'image/avif', 'webp' => 'image/webp'] as $ext => $type) {
        if (asset_exists('img/' . $base . '.' . $ext)) {
            $sources .= sprintf('<source srcset="%s" type="%s">', e(asset('img/' . $base . '.' . $ext)), $type);
        }
    }

    return sprintf(
        '<picture class="%s">%s<img src="%s" alt="%s" width="%d" height="%d" loading="%s" decoding="async"%s></picture>',
        e($classes),
        $sources,
        e(asset('img/' . $file)),
        e($alt),
        $width,
        $height,
        $eager ? 'eager' : 'lazy',
        $eager ? ' fetchpriority="high"' : ''
    );
}

function icon(string $name): string
{
    static $cache = [];
    if (!isset($cache[$name])) {
        $file = APP_DIR . '/views/icons/' . basename($name) . '.svg';
        $cache[$name] = is_file($file) ? trim((string) file_get_contents($file)) : '';
    }
    return $cache[$name];
}
