<?php

namespace JeffersonGoncalves\Filament\PageVisits\Resources\PageVisitResource\Widgets;

use Carbon\CarbonPeriod;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use JeffersonGoncalves\Filament\PageVisits\Support\Utils;
use JeffersonGoncalves\LaravelPageVisits\Models\PageVisit;

class StatsOverview extends BaseWidget
{
    protected ?string $pollingInterval = null;

    protected function getStats(): array
    {
        // Bounds fixed once so every query sees the same day, even across midnight.
        $start = today();
        $end = $start->copy()->addDay();

        // Same crawler-farm cut page-visits:aggregate-and-prune applies nightly.
        $today = PageVisit::query()
            ->excludingFarms($start, $end)
            ->where('visited_at', '>=', $start)
            ->where('visited_at', '<', $end)
            ->count();

        $dailyStatsTable = (string) config('page-visits.daily_stats_table', 'page_visit_daily_stats');

        if (Schema::hasTable($dailyStatsTable)) {
            // Aggregated days from the daily stats (farm already moved to bots
            // there, and they survive store_bots = false).
            $windowStart = $start->copy()->subDays((int) config('page-visits.retention_days', 400));
            $daily = DB::table($dailyStatsTable)
                ->where('date', '>=', $windowStart->toDateString())
                ->where('date', '<', $start->toDateString());
            $sums = $daily->clone()
                ->selectRaw('coalesce(sum(visits_count), 0) as humans, coalesce(sum(bot_visits_count), 0) as bots')
                ->first();
            $total = (int) $sums->humans;
            $bots = (int) $sums->bots;

            // Today, plus any retained day not aggregated yet (e.g. yesterday
            // between midnight and the scheduled aggregate run), read live day
            // by day with that day's farm cut.
            // ponytail: one farm lookup + 2 counts per missing day; an install
            // that never runs the aggregate pays that for every retained day.
            $covered = $daily->pluck('date')->map(fn ($date): string => substr((string) $date, 0, 10))->flip();
            $oldest = PageVisit::query()->where('visited_at', '>=', $windowStart)->min('visited_at');
            $days = $oldest === null ? [] : CarbonPeriod::create(Carbon::parse($oldest)->startOfDay(), $start->copy()->subDay());

            foreach ([...$days, $start] as $from) {
                if ($from < $start && $covered->has($from->toDateString())) {
                    continue;
                }

                [$humans, $dayBots] = $this->liveDayCounts(Carbon::instance($from));
                $total += $humans;
                $bots += $dayBots;
            }

            $botPercentage = $total + $bots > 0 ? round($bots / ($total + $bots) * 100, 1) : 0.0;
        } else {
            // Pre-1.1.0 install without daily stats: raw rows, one pass for total + bots.
            $counts = PageVisit::query()
                ->toBase()
                ->selectRaw('count(*) as total')
                ->selectRaw('count(case when is_bot = ? then 1 end) as bots', [true])
                ->first();
            $total = (int) $counts->total;
            $botPercentage = $total > 0 ? round((int) $counts->bots / $total * 100, 1) : 0.0;
        }

        $topCountry = PageVisit::query()
            ->whereNotNull('country')
            ->tap(Utils::onlyHumans(...))
            ->selectRaw('country, count(*) as aggregate')
            ->groupBy('country')
            ->orderByDesc('aggregate')
            ->value('country');

        return [
            Stat::make(__('filament-page-visits::resources/page-visit.stats.total_visits'), $total),
            Stat::make(__('filament-page-visits::resources/page-visit.stats.visits_today'), $today),
            Stat::make(__('filament-page-visits::resources/page-visit.stats.bot_percentage'), $botPercentage.'%'),
            Stat::make(__('filament-page-visits::resources/page-visit.stats.top_country'), $topCountry ?? __('filament-page-visits::resources/page-visit.stats.unknown')),
        ];
    }

    /**
     * Human and bot counts for one day of raw rows, cut like
     * page-visits:aggregate-and-prune: farm rows count as bots, datacenter
     * rows count as neither (visits_count excludes them too).
     *
     * @return array{int, int}
     */
    protected function liveDayCounts(Carbon $from): array
    {
        $to = $from->copy()->addDay();
        $farm = PageVisit::farmUserAgentHashes($from, $to);
        $day = fn (): Builder => PageVisit::query()
            ->where('visited_at', '>=', $from)
            ->where('visited_at', '<', $to);

        $humans = $day()
            ->where('is_bot', false)
            ->where('is_datacenter', false)
            // whereNotIn alone would drop null hashes (NULL NOT IN … is null).
            ->when($farm->isNotEmpty(), fn (Builder $q) => $q->where(
                fn (Builder $q) => $q->whereNull('user_agent_hash')->orWhereNotIn('user_agent_hash', $farm)
            ))
            ->count();

        $bots = $day()->where(
            fn (Builder $q) => $q->where('is_bot', true)->when($farm->isNotEmpty(), fn (Builder $q) => $q->orWhereIn('user_agent_hash', $farm))
        )->count();

        return [$humans, $bots];
    }
}
