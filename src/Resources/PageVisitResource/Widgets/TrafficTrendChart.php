<?php

namespace JeffersonGoncalves\Filament\PageVisits\Resources\PageVisitResource\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use JeffersonGoncalves\LaravelPageVisits\Models\PageVisit;

class TrafficTrendChart extends ChartWidget
{
    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 'full';

    public function getHeading(): ?string
    {
        return __('filament-page-visits::resources/page-visit.stats.traffic_trend');
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $days = collect(range(13, 0))->map(fn (int $daysAgo): Carbon => today()->subDays($daysAgo));

        // Aggregate in the database: hydrating every visit in the window
        // exhausts memory on busy sites. visited_at is a naive timestamp
        // written in the app timezone, so DB bucketing matches PHP's.
        $day = DB::connection()->getDriverName() === 'sqlsrv'
            ? 'CAST(visited_at AS DATE)'
            : 'DATE(visited_at)';

        $counts = PageVisit::query()
            ->where('visited_at', '>=', today()->subDays(13)->startOfDay())
            ->selectRaw("{$day} as day, count(*) as aggregate")
            ->groupBy(DB::raw($day))
            ->pluck('aggregate', 'day');

        return [
            'datasets' => [
                [
                    'label' => __('filament-page-visits::resources/page-visit.stats.total_visits'),
                    'data' => $days->map(fn (Carbon $day): int => (int) ($counts[$day->format('Y-m-d')] ?? 0))->all(),
                    'borderColor' => '#6366f1',
                    'backgroundColor' => 'rgba(99, 102, 241, 0.2)',
                    'fill' => true,
                ],
            ],
            'labels' => $days->map(fn (Carbon $day): string => $day->format('d/m'))->all(),
        ];
    }
}
