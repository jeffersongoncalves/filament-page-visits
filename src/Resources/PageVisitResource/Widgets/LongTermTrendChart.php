<?php

namespace JeffersonGoncalves\Filament\PageVisits\Resources\PageVisitResource\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use JeffersonGoncalves\LaravelPageVisits\Models\PageVisit;

class LongTermTrendChart extends ChartWidget
{
    protected static ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 'full';

    protected const DAYS = 90;

    /**
     * page_visit_daily_stats only exists from laravel-page-visits 1.1.0 —
     * hide the widget entirely on older installs instead of erroring.
     */
    public static function canView(): bool
    {
        return Schema::hasTable(static::dailyStatsTable());
    }

    public function getHeading(): ?string
    {
        return __('filament-page-visits::resources/page-visit.stats.long_term_trend');
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $days = collect(range(self::DAYS - 1, 0))->map(fn (int $daysAgo): Carbon => today()->subDays($daysAgo));

        $counts = DB::table(static::dailyStatsTable())
            ->where('date', '>=', today()->subDays(self::DAYS - 1)->toDateString())
            ->pluck('visits_count', 'date');

        // page-visits:aggregate-and-prune folds "yesterday" in — today's
        // count isn't written to the daily stats table yet, so read it live.
        $counts[today()->toDateString()] = PageVisit::query()
            ->where('is_bot', false)
            ->where('visited_at', '>=', today())
            ->where('visited_at', '<', today()->addDay())
            ->count();

        return [
            'datasets' => [
                [
                    'label' => __('filament-page-visits::resources/page-visit.stats.total_visits'),
                    'data' => $days->map(fn (Carbon $day): int => (int) ($counts[$day->toDateString()] ?? 0))->all(),
                    'borderColor' => '#22c55e',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.15)',
                    'fill' => true,
                ],
            ],
            'labels' => $days->map(fn (Carbon $day): string => $day->format('d/m'))->all(),
        ];
    }

    protected static function dailyStatsTable(): string
    {
        return (string) config('page-visits.daily_stats_table', 'page_visit_daily_stats');
    }
}
