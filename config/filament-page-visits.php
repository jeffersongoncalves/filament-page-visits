<?php

return [
    'metrics_page' => [
        // Filament defaults an unset slug to Str::kebab(class basename) —
        // "metrics-page" for any package's MetricsPage class. Explicit here
        // so this doesn't collide with another plugin's own MetricsPage at
        // the same URL in a host panel that installs both (confirmed:
        // jeffersongoncalves/filament-short-url ships an identically-named
        // page — this package deliberately mirrors its pattern).
        'slug' => 'page-visits-metrics',

        // Toggle individual metric widgets off without removing the page.
        // Useful on installs with huge page_visits tables where a query
        // (e.g. devices_chart's GROUP BY) is too costly to run on every load.
        'widgets' => [
            'stats_overview' => true,
            'security_overview' => true,
            'hourly_chart' => true,
            'traffic_trend' => true,
            'long_term_trend' => true,
            'response_time_trend' => true,
            'devices_chart' => true,
            'browsers_chart' => true,
            'operating_systems_chart' => true,
            'status_codes_chart' => true,
            'referer_types_chart' => true,
            'top_pages' => true,
            'top_referrers' => true,
            'top_countries' => true,
            'top_asn' => true,
        ],
    ],
];
