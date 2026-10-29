<?php

namespace JeffersonGoncalves\Filament\PageVisits\Resources\PageVisitResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use JeffersonGoncalves\LaravelPageVisits\Models\PageVisit;

class SecurityOverview extends BaseWidget
{
    protected static ?string $pollingInterval = null;

    protected function getStats(): array
    {
        // Single table pass instead of one count(*) per flag.
        $counts = PageVisit::query()
            ->toBase()
            ->selectRaw('count(*) as total')
            ->selectRaw('count(case when is_vpn then 1 end) as vpn')
            ->selectRaw('count(case when is_proxy then 1 end) as proxy')
            ->selectRaw('count(case when is_tor then 1 end) as tor')
            ->selectRaw('count(case when is_datacenter then 1 end) as datacenter')
            ->first();

        $total = (int) $counts->total;

        $percentage = fn (int $count): string => ($total > 0 ? round($count / $total * 100, 1) : 0.0).'%';

        $vpn = (int) $counts->vpn;
        $proxy = (int) $counts->proxy;
        $tor = (int) $counts->tor;
        $datacenter = (int) $counts->datacenter;

        return [
            Stat::make(__('filament-page-visits::resources/page-visit.fields.is_vpn'), $percentage($vpn)),
            Stat::make(__('filament-page-visits::resources/page-visit.fields.is_proxy'), $percentage($proxy)),
            Stat::make(__('filament-page-visits::resources/page-visit.fields.is_tor'), $percentage($tor)),
            Stat::make(__('filament-page-visits::resources/page-visit.fields.is_datacenter'), $percentage($datacenter)),
        ];
    }
}
