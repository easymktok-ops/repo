<?php

declare(strict_types=1);

require_once __DIR__ . '/../lib/csv.php';

function test_csv_neutraliza_formulas(): void
{
    assert_same("'=HYPERLINK(\"http://x\")", csv_safe_cell('=HYPERLINK("http://x")'));
    assert_same("'@SUM(A1)", csv_safe_cell('@SUM(A1)'));
    assert_same("'+cmd|' /C calc'!A0", csv_safe_cell("+cmd|' /C calc'!A0"));
    assert_same("'-2+3", csv_safe_cell('-2+3'));
    assert_same("'\tx", csv_safe_cell("\tx"));
}

function test_csv_respeta_datos_normales(): void
{
    assert_same('+52 55 1234 5678', csv_safe_cell('+52 55 1234 5678'));
    assert_same('-1500', csv_safe_cell('-1500'));
    assert_same('Mariana López', csv_safe_cell('Mariana López'));
    assert_same(3500, csv_safe_cell(3500));
    assert_same('', csv_safe_cell(''));
}
