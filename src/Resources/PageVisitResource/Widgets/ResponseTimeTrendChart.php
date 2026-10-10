<?php

namespace JeffersonGoncalves\Filament\PageVisits\Resources\PageVisitResource\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ResponseTimeTrendChart extends ChartWidget
{
    protected static ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 'full';

    protected const DAYS = 90;

    /**
     * response_time_stats only exists from laravel-page-visits 1.3.0 —
     * hide the widget entirely on older installs instead of erroring.
     */
    public static function canView(): bool
    {
        return Schema::hasColumn(static::dailyStatsTable(), 'response_time_stats');
    }

    public function getHeading(): ?string
    {
        return __('filament-page-visits::resources/page-visit.stats.response_time_trend');
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $days = collect(range(self::DAYS - 1, 0))->map(fn (int $daysAgo): Carbon => today()->subDays($daysAgo));

        // ponytail: today has no row until page-visits:aggregate-and-prune runs, so it shows as a gap.
        $stats = DB::table(static::dailyStatsTable())
            ->where('date', '>=', today()->subDays(self::DAYS - 1)->toDateString())
            ->whereNotNull('response_time_stats')
            ->pluck('response_time_stats', 'date')
            ->map(fn (string $json): array => json_decode($json, true));

        $series = fn (string $key): array => $days
            ->map(fn (Carbon $day): ?int => $stats[$day->toDateString()][$key] ?? null)
            ->all();

        return [
            'datasets' => [
                ['label' => 'p50', 'data' => $series('p50'), 'borderColor' => '#22c55e', 'backgroundColor' => '#22c55e'],
                ['label' => 'p95', 'data' => $series('p95'), 'borderColor' => '#f59e0b', 'backgroundColor' => '#f59e0b'],
                ['label' => 'p99', 'data' => $series('p99'), 'borderColor' => '#ef4444', 'backgroundColor' => '#ef4444'],
            ],
            'labels' => $days->map(fn (Carbon $day): string => $day->format('d/m'))->all(),
        ];
    }

    protected static function dailyStatsTable(): string
    {
        return (string) config('page-visits.daily_stats_table', 'page_visit_daily_stats');
    }
}
