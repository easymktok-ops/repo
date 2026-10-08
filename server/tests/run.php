<?php
/**
 * Mini-runner de tests sin composer. Carga server/tests/*_test.php, ejecuta
 * las funciones test_* y sale con codigo 1 si algo falla.
 * Uso: php server/tests/run.php [filtro]
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

final class AssertionFailed extends Exception
{
}

function assert_same($expected, $actual, string $msg = ''): void
{
    if ($expected !== $actual) {
        throw new AssertionFailed(
            ($msg !== '' ? $msg . "\n" : '')
            . '  esperado: ' . var_export($expected, true) . "\n"
            . '  obtenido: ' . var_export($actual, true)
        );
    }
}

function assert_true($cond, string $msg = ''): void
{
    if ($cond !== true) {
        throw new AssertionFailed($msg !== '' ? $msg : 'se esperaba true');
    }
}

function assert_throws(string $class, callable $fn, string $msg = ''): Throwable
{
    try {
        $fn();
    } catch (Throwable $e) {
        if ($e instanceof $class) {
            return $e;
        }
        throw new AssertionFailed(($msg !== '' ? $msg . ': ' : '') . 'se lanzo ' . get_class($e) . ' y se esperaba ' . $class);
    }
    throw new AssertionFailed(($msg !== '' ? $msg . ': ' : '') . 'no se lanzo ' . $class);
}

require_once __DIR__ . '/../lib/bootstrap.php';
ini_set('error_log', PHP_OS_FAMILY === 'Windows' ? 'NUL' : '/dev/null');

$filter = $argv[1] ?? '';
$before = get_defined_functions()['user'];
foreach (glob(__DIR__ . '/*_test.php') as $file) {
    require_once $file;
}
$tests = array_filter(
    array_diff(get_defined_functions()['user'], $before),
    function (string $f) use ($filter): bool {
        return strpos($f, 'test_') === 0 && ($filter === '' || strpos($f, strtolower($filter)) !== false);
    }
);
sort($tests);

$fail = 0;
foreach ($tests as $t) {
    try {
        $t();
        echo "  ok   {$t}\n";
    } catch (Throwable $e) {
        $fail++;
        echo "  FALLA {$t}\n" . $e->getMessage() . "\n";
        if (!$e instanceof AssertionFailed) {
            echo '  en ' . $e->getFile() . ':' . $e->getLine() . "\n";
        }
    }
}
$n = count($tests);
echo "\n" . ($n - $fail) . "/{$n} tests en verde" . ($fail ? ", {$fail} fallaron" : '') . ' (PHP ' . PHP_VERSION . ")\n";
exit($fail ? 1 : 0);
