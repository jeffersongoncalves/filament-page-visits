<?php

namespace JeffersonGoncalves\Filament\PageVisits\Pages;

use Filament\Pages\Page;
use Filament\Panel;
use JeffersonGoncalves\Filament\PageVisits\Concerns\HasPluginNavigationGroup;
use JeffersonGoncalves\Filament\PageVisits\Resources\PageVisitResource\Widgets\BrowsersChart;
use JeffersonGoncalves\Filament\PageVisits\Resources\PageVisitResource\Widgets\DevicesChart;
use JeffersonGoncalves\Filament\PageVisits\Resources\PageVisitResource\Widgets\HourlyChart;
use JeffersonGoncalves\Filament\PageVisits\Resources\PageVisitResource\Widgets\LongTermTrendChart;
use JeffersonGoncalves\Filament\PageVisits\Resources\PageVisitResource\Widgets\OperatingSystemsChart;
use JeffersonGoncalves\Filament\PageVisits\Resources\PageVisitResource\Widgets\RefererTypesChart;
use JeffersonGoncalves\Filament\PageVisits\Resources\PageVisitResource\Widgets\ResponseTimeTrendChart;
use JeffersonGoncalves\Filament\PageVisits\Resources\PageVisitResource\Widgets\SecurityOverview;
use JeffersonGoncalves\Filament\PageVisits\Resources\PageVisitResource\Widgets\StatsOverview;
use JeffersonGoncalves\Filament\PageVisits\Resources\PageVisitResource\Widgets\StatusCodesChart;
use JeffersonGoncalves\Filament\PageVisits\Resources\PageVisitResource\Widgets\TopAsn;
use JeffersonGoncalves\Filament\PageVisits\Resources\PageVisitResource\Widgets\TopCountries;
use JeffersonGoncalves\Filament\PageVisits\Resources\PageVisitResource\Widgets\TopPages;
use JeffersonGoncalves\Filament\PageVisits\Resources\PageVisitResource\Widgets\TopReferrers;
use JeffersonGoncalves\Filament\PageVisits\Resources\PageVisitResource\Widgets\TrafficTrendChart;
use JeffersonGoncalves\Filament\PageVisits\Support\Utils;

class MetricsPage extends Page
{
    use HasPluginNavigationGroup;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static string $view = 'filament-page-visits::pages.metrics-page';

    public static function getSlug(?Panel $panel = null): string
    {
        return Utils::getMetricsPageSlug();
    }

    public function getTitle(): string
    {
        return __('filament-page-visits::resources/page-visit.metrics.title');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament-page-visits::resources/page-visit.metrics.title');
    }

    protected function getHeaderWidgets(): array
    {
        return array_values(array_filter([
            Utils::isMetricsWidgetEnabled('stats_overview') ? StatsOverview::class : null,
            Utils::isMetricsWidgetEnabled('security_overview') ? SecurityOverview::class : null,
            Utils::isMetricsWidgetEnabled('hourly_chart') ? HourlyChart::class : null,
            Utils::isMetricsWidgetEnabled('traffic_trend') ? TrafficTrendChart::class : null,
            Utils::isMetricsWidgetEnabled('long_term_trend') ? LongTermTrendChart::class : null,
            Utils::isMetricsWidgetEnabled('response_time_trend') ? ResponseTimeTrendChart::class : null,
            Utils::isMetricsWidgetEnabled('devices_chart') ? DevicesChart::class : null,
            Utils::isMetricsWidgetEnabled('browsers_chart') ? BrowsersChart::class : null,
            Utils::isMetricsWidgetEnabled('operating_systems_chart') ? OperatingSystemsChart::class : null,
            Utils::isMetricsWidgetEnabled('status_codes_chart') ? StatusCodesChart::class : null,
            Utils::isMetricsWidgetEnabled('referer_types_chart') ? RefererTypesChart::class : null,
        ]));
    }

    protected function getFooterWidgets(): array
    {
        return array_values(array_filter([
            Utils::isMetricsWidgetEnabled('top_pages') ? TopPages::class : null,
            Utils::isMetricsWidgetEnabled('top_referrers') ? TopReferrers::class : null,
            Utils::isMetricsWidgetEnabled('top_countries') ? TopCountries::class : null,
            Utils::isMetricsWidgetEnabled('top_asn') ? TopAsn::class : null,
        ]));
    }
}
