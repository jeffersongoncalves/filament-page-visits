<?php

namespace JeffersonGoncalves\Filament\PageVisits\Resources\PageVisitResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use JeffersonGoncalves\LaravelPageVisits\Models\PageVisit;

class StatsOverview extends BaseWidget
{
    protected static ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $total = PageVisit::query()->count();
        $today = PageVisit::query()->whereDate('visited_at', today())->count();
        $bots = PageVisit::query()->where('is_bot', true)->count();
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
