<?php
declare(strict_types=1);

function content_all(): array
{
    static $content = null;
    if ($content !== null) {
        return $content;
    }

    $content = require APP_DIR . '/content/defaults.php';

    $overrides = STORAGE_DIR . '/content.json';
    if (is_file($overrides)) {
        $data = json_decode((string) file_get_contents($overrides), true);
        if (is_array($data)) {
            $content = array_replace_recursive($content, $data);
        }
    }

    return $content;
}

/** Lee contenido con notación de puntos: content('hero.cta_label'). */
function content(string $key, mixed $default = null): mixed
{
    $value = content_all();
    foreach (explode('.', $key) as $segment) {
        if (!is_array($value) || !array_key_exists($segment, $value)) {
            return $default;
        }
        $value = $value[$segment];
    }
    return $value;
}

function is_placeholder(mixed $value): bool
{
    return is_string($value) && str_starts_with($value, '{{');
}

/** Próxima fecha de una función semanal (weekday ISO 1-7), en hora de Bogotá. */
function next_weekly_date(int $weekday, string $time): DateTimeImmutable
{
    $now = new DateTimeImmutable('now');
    [$h, $m] = array_map('intval', explode(':', $time));
    $candidate = $now->setTime($h, $m);
    $diff = ($weekday - (int) $candidate->format('N') + 7) % 7;
    $candidate = $candidate->modify("+{$diff} days");
    if ($candidate <= $now) {
        $candidate = $candidate->modify('+7 days');
    }
    return $candidate;
}

function format_time_12h(string $time): string
{
    if ($time === '') {
        return '';
    }
    $dt = DateTimeImmutable::createFromFormat('H:i', $time);
    return $dt ? $dt->format('g:i') . "\u{00A0}" . ($dt->format('A') === 'AM' ? 'AM' : 'PM') : $time;
}
