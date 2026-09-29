<?php
/**
 * Lint de compatibilidad con PHP 7.4 para el backend.
 *  1. php -l de cada archivo con el PHP local.
 *  2. Busca construcciones de PHP 8+ por tokens (match, ?->, enum, readonly,
 *     atributos, separadores numericos, funciones nuevas).
 *  3. Si hay phpcs con PHPCompatibility (variable PHPCS o vendor/bin/phpcs),
 *     corre el estandar con testVersion 7.4-.
 * La bandera de tarifas no protege contra errores de sintaxis: todo archivo
 * que se carga sin condicion debe compilar en 7.4.
 * Uso: php server/tests/lint74.php
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$root = dirname(__DIR__);
$files = [];
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
foreach ($it as $f) {
    if ($f->getExtension() === 'php' && strpos($f->getPathname(), DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR) === false) {
        $files[] = $f->getPathname();
    }
}
sort($files);

$forbiddenTokens = [];
foreach (['T_MATCH', 'T_NULLSAFE_OBJECT_OPERATOR', 'T_ENUM', 'T_READONLY', 'T_ATTRIBUTE'] as $name) {
    if (defined($name)) {
        $forbiddenTokens[constant($name)] = $name;
    }
}
$forbiddenFns = ['str_contains', 'str_starts_with', 'str_ends_with', 'array_is_list', 'get_debug_type', 'fdiv', 'get_resource_id'];

$problems = [];
foreach ($files as $file) {
    $out = [];
    $code = 0;
    exec(escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($file) . ' 2>&1', $out, $code);
    if ($code !== 0) {
        $problems[] = $file . ': ' . implode(' ', $out);
        continue;
    }
    $tokens = token_get_all((string) file_get_contents($file));
    foreach ($tokens as $i => $t) {
        if (!is_array($t)) {
            continue;
        }
        [$id, $text, $line] = $t;
        if (isset($forbiddenTokens[$id])) {
            $problems[] = "{$file}:{$line}: {$forbiddenTokens[$id]} no existe en PHP 7.4";
        } elseif (($id === T_LNUMBER || $id === T_DNUMBER) && strpos($text, '_') !== false) {
            $problems[] = "{$file}:{$line}: separador numerico {$text}";
        } elseif ($id === T_STRING && in_array(strtolower($text), $forbiddenFns, true)) {
            $next = $tokens[$i + 1] ?? null;
            if ($next === '(') {
                $problems[] = "{$file}:{$line}: {$text}() no existe en PHP 7.4";
            }
        }
    }
}

$phpcs = getenv('PHPCS') ?: '';
if ($phpcs === '' && is_file(dirname($root) . '/vendor/bin/phpcs')) {
    $phpcs = dirname($root) . '/vendor/bin/phpcs';
}
$ranPhpcs = false;
if ($phpcs !== '') {
    $out = [];
    $code = 0;
    exec(escapeshellarg($phpcs) . ' --standard=PHPCompatibility --runtime-set testVersion 7.4- -q --report=emacs ' . escapeshellarg($root) . ' 2>&1', $out, $code);
    $ranPhpcs = true;
    if ($code !== 0) {
        foreach ($out as $l) {
            $problems[] = 'PHPCompatibility: ' . $l;
        }
    }
}

foreach ($problems as $p) {
    echo $p . "\n";
}
echo count($files) . ' archivos revisados con PHP ' . PHP_VERSION
    . ($ranPhpcs ? ' + PHPCompatibility 7.4-' : ' (sin phpcs: define PHPCS=/ruta/phpcs para el chequeo completo)')
    . ': ' . ($problems ? count($problems) . ' problemas' : 'OK') . "\n";
exit($problems ? 1 : 0);
