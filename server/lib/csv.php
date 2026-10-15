<?php
/**
 * Exportacion CSV segura: evita la "inyeccion de formulas" (CSV/Excel injection). Cualquier dato que
 * escribe el cliente (nombre, notas, UTM de un enlace) podria empezar con = + - @ y Excel lo
 * ejecutaria como formula al abrir el archivo. Se antepone un apostrofo a esas celdas.
 * Telefonos y montos ("+52 55 1234", "-1500") se dejan igual: solo digitos y separadores.
 */

declare(strict_types=1);

function csv_safe_cell($v)
{
    if (!is_string($v) || $v === '') {
        return $v;
    }
    if (preg_match('/^[=@\t\r]/', $v)) {
        return "'" . $v;
    }
    if (preg_match('/^[+\-]/', $v) && !preg_match('/^[+\-][\d\s().\-]*$/', $v)) {
        return "'" . $v;
    }
    return $v;
}

/** @return array fila con cada celda neutralizada */
function csv_safe_row(array $row): array
{
    return array_map('csv_safe_cell', $row);
}
