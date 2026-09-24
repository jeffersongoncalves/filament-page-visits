<?php

namespace JeffersonGoncalves\Filament\PageVisits\Resources\PageVisitResource\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;
use JeffersonGoncalves\LaravelPageVisits\Models\PageVisit;

class HourlyChart extends ChartWidget
{
    protected static ?string $pollingInterval = null;

    public function getHeading(): ?string
    {
        return __('filament-page-visits::resources/page-visit.stats.hourly');
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        // Aggregate in the database: hydrating every visit in the window
        // exhausts memory on busy sites.
        $hour = match (DB::connection()->getDriverName()) {
            'mysql', 'mariadb' => 'HOUR(visited_at)',
            'pgsql' => 'CAST(EXTRACT(HOUR FROM visited_at) AS INTEGER)',
            'sqlsrv' => 'DATEPART(HOUR, visited_at)',
            default => "CAST(strftime('%H', visited_at) AS INTEGER)",
        };

        $counts = PageVisit::query()
            ->where('visited_at', '>=', now()->subHours(23)->startOfHour())
            ->selectRaw("{$hour} as hour, count(*) as aggregate")
            ->groupBy(DB::raw($hour))
            ->pluck('aggregate', 'hour');

        $labels = range(0, 23);

        return [
            'datasets' => [
                [
                    'label' => __('filament-page-visits::resources/page-visit.stats.total_visits'),
                    'data' => array_map(fn (int $hour): int => (int) ($counts[$hour] ?? 0), $labels),
                    'backgroundColor' => '#6366f1',
                ],
            ],
            'labels' => array_map(fn (int $hour): string => sprintf('%02d:00', $hour), $labels),
        ];
    }
}
