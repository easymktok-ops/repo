<?php
declare(strict_types=1);

/**
 * $page: title, description, path, indexable (bool), og_image (asset path)
 */
function seo_head(array $page): string
{
    $title = (string) ($page['title'] ?? content('site.name'));
    $description = (string) ($page['description'] ?? '');
    $canonical = url($page['path'] ?? '/');
    $indexable = ($page['indexable'] ?? true) && APP_ENV === 'production';

    $tags = [];
    $tags[] = '<title>' . e($title) . '</title>';
    if ($description !== '' && !is_placeholder($description)) {
        $tags[] = '<meta name="description" content="' . e($description) . '">';
        $tags[] = '<meta property="og:description" content="' . e($description) . '">';
    }
    $tags[] = '<meta name="robots" content="' . ($indexable ? 'index, follow' : 'noindex, nofollow') . '">';
    $tags[] = '<link rel="canonical" href="' . e($canonical) . '">';
    $tags[] = '<meta property="og:type" content="website">';
    $tags[] = '<meta property="og:locale" content="es_CO">';
    $tags[] = '<meta property="og:site_name" content="' . e(content('site.name')) . '">';
    $tags[] = '<meta property="og:title" content="' . e($title) . '">';
    $tags[] = '<meta property="og:url" content="' . e($canonical) . '">';
    if (!empty($page['og_image']) && asset_exists($page['og_image'])) {
        $tags[] = '<meta property="og:image" content="' . e(url('assets/' . $page['og_image'])) . '">';
    }
    $tags[] = '<meta name="twitter:card" content="summary_large_image">';

    return implode("\n", $tags);
}

function schema_organization(): array
{
    $sameAs = array_values(array_filter([
        content('site.instagram'),
        content('site.facebook'),
        content('site.tripadvisor'),
    ]));

    $org = [
        '@type' => 'PerformingGroup',
        '@id'   => url('/#organization'),
        'name'  => content('site.name'),
        'url'   => url('/'),
    ];
    if (asset_exists('img/logo.png')) {
        $org['logo'] = url('assets/img/logo.png');
    }
    if ($sameAs) {
        $org['sameAs'] = $sameAs;
    }
    return $org;
}

function schema_events(): array
{
    $events = [];
    foreach (content('programacion.events', []) as $event) {
        if (!$event['buyable'] || empty($event['weekday']) || empty($event['prices'])) {
            continue;
        }

        $start = next_weekly_date((int) $event['weekday'], $event['time']);
        $offers = array_map(static fn(array $price): array => [
            '@type'         => 'Offer',
            'name'          => $price['label'],
            'price'         => (string) $price['amount'],
            'priceCurrency' => 'COP',
            'availability'  => 'https://schema.org/InStock',
            'url'           => url($event['cta_url']),
            'validFrom'     => (new DateTimeImmutable('today'))->format(DATE_ATOM),
        ], $event['prices']);

        $events[] = [
            '@type'               => 'Event',
            'name'                => content('site.name') . ' — ' . $event['city'],
            'startDate'           => $start->format(DATE_ATOM),
            'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
            'eventStatus'         => 'https://schema.org/EventScheduled',
            'eventSchedule'       => [
                '@type'           => 'Schedule',
                'repeatFrequency' => 'P1W',
                'byDay'           => 'https://schema.org/' . ['', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'][(int) $event['weekday']],
                'startTime'       => $event['time'],
                'scheduleTimezone' => 'America/Bogota',
            ],
            'location' => [
                '@type'   => 'Place',
                'name'    => $event['venue'],
                'address' => [
                    '@type'           => 'PostalAddress',
                    'addressLocality' => $event['city'],
                    'addressCountry'  => 'CO',
                ],
            ],
            'performer' => ['@id' => url('/#organization')],
            'organizer' => ['@id' => url('/#organization')],
            'offers'    => $offers,
        ];
    }
    return $events;
}

function schema_script(array $nodes): string
{
    $graph = ['@context' => 'https://schema.org', '@graph' => $nodes];
    $json = json_encode($graph, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP);
    return '<script type="application/ld+json">' . $json . '</script>';
}
