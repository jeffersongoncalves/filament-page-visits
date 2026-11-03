<?php

namespace JeffersonGoncalves\Filament\PageVisits\Resources\PageVisitResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use JeffersonGoncalves\LaravelPageVisits\Models\PageVisit;

class StatsOverview extends BaseWidget
{
    protected ?string $pollingInterval = null;

    protected function getStats(): array
    {
        // One pass for total + bots; "today" as a range so the visited_at index is used.
        $counts = PageVisit::query()
            ->toBase()
            ->selectRaw('count(*) as total')
            ->selectRaw('count(case when is_bot = ? then 1 end) as bots', [true])
            ->first();
        $total = (int) $counts->total;
        $bots = (int) $counts->bots;
        // Bounds fixed once so both queries see the same day, even across midnight.
        $start = today();
        $end = $start->copy()->addDay();
        $todayQuery = fn () => PageVisit::query()
            ->where('visited_at', '>=', $start)
            ->where('visited_at', '<', $end);

        // Same crawler-farm cut page-visits:aggregate-and-prune applies nightly:
        // a UA seen from more than farm_ua_ip_threshold IPs today is not a visitor.
        // Kept as a subquery so the count binds no per-hash parameters.
        $threshold = config('page-visits.farm_ua_ip_threshold');
        $farm = (! is_numeric($threshold) || (int) $threshold <= 0) ? null : $todayQuery()
            ->select('user_agent_hash')
            ->whereNotNull('user_agent_hash')
            ->whereNotNull('ip_hash')
            ->groupBy('user_agent_hash')
            ->havingRaw('count(distinct ip_hash) > ?', [(int) $threshold]);

        $today = $todayQuery()
            ->when($farm !== null, fn ($q) => $q->where(
                // whereNotIn alone would drop null hashes (NULL NOT IN … is null).
                fn ($q) => $q->whereNull('user_agent_hash')->orWhereNotIn('user_agent_hash', $farm)
            ))
            ->count();
        $botPercentage = $total > 0 ? round($bots / $total * 100, 1) : 0.0;

        $topCountry = PageVisit::query()
            ->whereNotNull('country')
            ->where('is_bot', false)
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
