<?php

namespace JeffersonGoncalves\Filament\PageVisits\Resources\PageVisitResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
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

        // Same crawler-farm cut page-visits:aggregate-and-prune applies nightly,
        // looked up once and reused (as the command does).
        $farm = PageVisit::farmUserAgentHashes($start, $end);
        $todayQuery = fn (): Builder => PageVisit::query()
            ->where('visited_at', '>=', $start)
            ->where('visited_at', '<', $end);
        // whereNotIn alone would drop null hashes (NULL NOT IN … is null).
        $notFarm = fn (Builder $q) => $q->when($farm->isNotEmpty(), fn (Builder $q) => $q->where(
            fn (Builder $q) => $q->whereNull('user_agent_hash')->orWhereNotIn('user_agent_hash', $farm)
        ));

        $today = $todayQuery()->tap($notFarm)->count();

        $dailyStatsTable = (string) config('page-visits.daily_stats_table', 'page_visit_daily_stats');

        if (Schema::hasTable($dailyStatsTable)) {
            // Past days from the daily stats (farm already moved to bots there,
            // and they survive store_bots = false), today read live.
            $daily = DB::table($dailyStatsTable)
                ->where('date', '>=', $start->copy()->subDays((int) config('page-visits.retention_days', 400))->toDateString())
                ->where('date', '<', $start->toDateString())
                ->selectRaw('coalesce(sum(visits_count), 0) as humans, coalesce(sum(bot_visits_count), 0) as bots')
                ->first();

            $total = (int) $daily->humans + $todayQuery()->where('is_bot', false)->tap($notFarm)->count();
            $bots = (int) $daily->bots + $todayQuery()->where(
                fn (Builder $q) => $q->where('is_bot', true)->when($farm->isNotEmpty(), fn (Builder $q) => $q->orWhereIn('user_agent_hash', $farm))
            )->count();
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
            ->where('is_bot', false)
            ->tap(Utils::excludeFarms(...))
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
}
