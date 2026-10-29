<?php

use Illuminate\Support\Facades\DB;
use JeffersonGoncalves\Filament\PageVisits\FilamentPageVisitsPlugin;
use JeffersonGoncalves\Filament\PageVisits\Pages\MetricsPage;
use JeffersonGoncalves\Filament\PageVisits\Resources\PageVisitResource;
use JeffersonGoncalves\Filament\PageVisits\Resources\PageVisitResource\Pages\ListPageVisits;
use JeffersonGoncalves\Filament\PageVisits\Resources\PageVisitResource\Pages\ViewPageVisit;
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
use JeffersonGoncalves\Filament\PageVisits\Tests\Factories\PageVisitFactory;
use JeffersonGoncalves\Filament\PageVisits\Tests\Factories\UserFactory;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->admin = UserFactory::new()->create();

    filament()->setCurrentPanel(filament()->getPanel('admin'));

    $this->actingAs($this->admin);
});

it('can render the list page', function () {
    livewire(ListPageVisits::class)->assertSuccessful();
});

it('lists the expected table columns', function () {
    $visit = PageVisitFactory::new()->create(['path' => '/listme']);

    livewire(ListPageVisits::class)
        ->assertCanSeeTableRecords([$visit])
        ->assertTableColumnExists('visited_at')
        ->assertTableColumnExists('path')
        ->assertTableColumnExists('method')
        ->assertTableColumnExists('status_code')
        ->assertTableColumnExists('device_type')
        ->assertTableColumnExists('browser')
        ->assertTableColumnExists('operating_system')
        ->assertTableColumnExists('country')
        ->assertTableColumnExists('referer_type')
        ->assertTableColumnExists('is_bot');
});

it('can render the view page', function () {
    $visit = PageVisitFactory::new()->create();

    livewire(ViewPageVisit::class, ['record' => $visit->getRouteKey()])->assertSuccessful();
});

it('can render the dedicated metrics page', function () {
    PageVisitFactory::new()->create();

    livewire(MetricsPage::class)->assertSuccessful();
});

it('defaults the metrics page slug and allows overriding it via config', function () {
    expect(MetricsPage::getSlug())->toBe('page-visits-metrics');

    config()->set('filament-page-visits.metrics_page.slug', 'custom-metrics-slug');

    expect(MetricsPage::getSlug())->toBe('custom-metrics-slug');

    config()->set('filament-page-visits.metrics_page.slug', 'page-visits-metrics');
});

it('renders the metrics widgets without polling', function () {
    PageVisitFactory::new()->create();

    livewire(StatsOverview::class)->assertSuccessful()->assertDontSee('wire:poll', escape: false);
    livewire(SecurityOverview::class)->assertSuccessful()->assertDontSee('wire:poll', escape: false);
    livewire(HourlyChart::class)->assertSuccessful()->assertDontSee('wire:poll', escape: false);
    livewire(TrafficTrendChart::class)->assertSuccessful()->assertDontSee('wire:poll', escape: false);
    livewire(LongTermTrendChart::class)->assertSuccessful()->assertDontSee('wire:poll', escape: false);
    livewire(ResponseTimeTrendChart::class)->assertSuccessful()->assertDontSee('wire:poll', escape: false);
    livewire(DevicesChart::class)->assertSuccessful()->assertDontSee('wire:poll', escape: false);
    livewire(BrowsersChart::class)->assertSuccessful()->assertDontSee('wire:poll', escape: false);
    livewire(OperatingSystemsChart::class)->assertSuccessful()->assertDontSee('wire:poll', escape: false);
    livewire(StatusCodesChart::class)->assertSuccessful()->assertDontSee('wire:poll', escape: false);
    livewire(RefererTypesChart::class)->assertSuccessful()->assertDontSee('wire:poll', escape: false);
    livewire(TopPages::class)->assertSuccessful()->assertDontSee('wire:poll', escape: false);
    livewire(TopReferrers::class)->assertSuccessful()->assertDontSee('wire:poll', escape: false);
    livewire(TopCountries::class)->assertSuccessful()->assertDontSee('wire:poll', escape: false);
    livewire(TopAsn::class)->assertSuccessful()->assertDontSee('wire:poll', escape: false);
});

it('excludes bot traffic from top country stats', function () {
    PageVisitFactory::new()->create(['country' => 'Bot-only Land', 'is_bot' => true]);
    PageVisitFactory::new()->create(['country' => 'Real Visitor Land', 'is_bot' => false]);

    livewire(TopCountries::class)
        ->assertSuccessful()
        ->assertSee('Real Visitor Land')
        ->assertDontSee('Bot-only Land');

    livewire(StatsOverview::class)
        ->assertSuccessful()
        ->assertSee('Real Visitor Land')
        ->assertDontSee('Bot-only Land');
});

it('computes overview stats with single-pass aggregates', function () {
    $this->travelTo('2026-09-24 15:30:00');

    PageVisitFactory::new()->create(['visited_at' => '2026-09-24 00:00:00', 'is_bot' => true, 'is_vpn' => true, 'is_proxy' => false, 'is_tor' => false, 'is_datacenter' => true]);
    PageVisitFactory::new()->create(['visited_at' => '2026-09-24 23:59:59', 'is_bot' => false, 'is_vpn' => false, 'is_proxy' => true, 'is_tor' => false, 'is_datacenter' => true]);
    PageVisitFactory::new()->create(['visited_at' => '2026-09-23 23:59:59', 'is_bot' => false, 'is_vpn' => false, 'is_proxy' => false, 'is_tor' => false, 'is_datacenter' => false]);
    PageVisitFactory::new()->create(['visited_at' => '2026-09-25 00:00:00', 'is_bot' => false, 'is_vpn' => false, 'is_proxy' => false, 'is_tor' => false, 'is_datacenter' => false]);

    $values = fn (string $widget): array => array_map(
        fn ($stat) => $stat->getValue(),
        (fn () => $this->getStats())->call(new $widget),
    );

    // total, today (range bounds: 00:00:00 inclusive, next midnight exclusive), bot %.
    expect(array_slice($values(StatsOverview::class), 0, 3))->toBe([4, 2, '25%'])
        ->and($values(SecurityOverview::class))->toBe(['25%', '25%', '0%', '50%']);
});

it('buckets traffic trend and hourly charts in the database', function () {
    $this->travelTo('2026-09-24 15:30:00');

    foreach (['2026-09-24 15:10:00', '2026-09-24 15:20:00', '2026-09-24 09:00:00', '2026-09-23 16:45:00', '2026-09-11 00:00:00', '2026-09-10 23:59:59'] as $visitedAt) {
        PageVisitFactory::new()->create(['visited_at' => $visitedAt]);
    }

    $getData = fn () => $this->getData();

    $trend = $getData->call(new TrafficTrendChart)['datasets'][0]['data'];
    $hourly = $getData->call(new HourlyChart)['datasets'][0]['data'];

    // 14 days, 2026-09-11 .. 2026-09-24; 2026-09-10 is out of the window.
    expect($trend)->toBe([1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 3])
        // 2026-09-23 16:45 falls in the last 24h (hour 16); older visits are excluded.
        ->and($hourly)->toBe(array_replace(array_fill(0, 24, 0), [9 => 1, 15 => 2, 16 => 1]));
});

it('renders every metrics widget by default', function () {
    $page = new MetricsPage;

    expect([...$page->getVisibleHeaderWidgets(), ...$page->getVisibleFooterWidgets()])->toEqualCanonicalizing([
        StatsOverview::class,
        SecurityOverview::class,
        HourlyChart::class,
        TrafficTrendChart::class,
        LongTermTrendChart::class,
        ResponseTimeTrendChart::class,
        DevicesChart::class,
        BrowsersChart::class,
        OperatingSystemsChart::class,
        StatusCodesChart::class,
        RefererTypesChart::class,
        TopPages::class,
        TopReferrers::class,
        TopCountries::class,
        TopAsn::class,
    ]);
});

it('hides the long-term trend widget when page_visit_daily_stats does not exist', function () {
    // Point at a missing table rather than dropping it: Testbench rolls the
    // package migrations back after each test and that needs the real table.
    config()->set('page-visits.daily_stats_table', 'missing_daily_stats');

    $page = new MetricsPage;

    expect($page->getVisibleHeaderWidgets())->not->toContain(LongTermTrendChart::class);

    config()->set('page-visits.daily_stats_table', 'page_visit_daily_stats');
});

it('charts daily response time percentiles, leaving gaps for days without stats', function () {
    $this->travelTo('2026-09-24 15:30:00');

    DB::table(config('page-visits.daily_stats_table', 'page_visit_daily_stats'))->insert([
        ['date' => '2026-09-23', 'response_time_stats' => json_encode(['count' => 3, 'avg' => 40, 'min' => 10, 'max' => 90, 'p50' => 20, 'p95' => 80, 'p99' => 90])],
        ['date' => '2026-09-22', 'response_time_stats' => null],
    ]);

    $datasets = (fn () => $this->getData())->call(new ResponseTimeTrendChart)['datasets'];

    expect(array_column($datasets, 'label'))->toBe(['p50', 'p95', 'p99'])
        ->and(array_slice($datasets[0]['data'], -3))->toBe([null, 20, null])
        ->and(array_slice($datasets[1]['data'], -3))->toBe([null, 80, null])
        ->and(array_slice($datasets[2]['data'], -3))->toBe([null, 90, null]);
});

it('hides the response time widget when response_time_stats does not exist', function () {
    // Any existing table without the column stands in for a pre-1.3.0 install.
    config()->set('page-visits.daily_stats_table', 'page_visits');

    expect((new MetricsPage)->getVisibleHeaderWidgets())->not->toContain(ResponseTimeTrendChart::class)
        ->toContain(LongTermTrendChart::class);

    config()->set('page-visits.daily_stats_table', 'page_visit_daily_stats');
});

it('can disable individual metrics widgets via config', function () {
    config()->set('filament-page-visits.metrics_page.widgets.devices_chart', false);
    config()->set('filament-page-visits.metrics_page.widgets.top_referrers', false);
    config()->set('filament-page-visits.metrics_page.widgets.top_asn', false);

    $page = new MetricsPage;

    expect([...$page->getVisibleHeaderWidgets(), ...$page->getVisibleFooterWidgets()])->toEqualCanonicalizing([
        StatsOverview::class,
        SecurityOverview::class,
        HourlyChart::class,
        TrafficTrendChart::class,
        LongTermTrendChart::class,
        ResponseTimeTrendChart::class,
        BrowsersChart::class,
        OperatingSystemsChart::class,
        StatusCodesChart::class,
        RefererTypesChart::class,
        TopPages::class,
        TopCountries::class,
    ]);

    config()->set('filament-page-visits.metrics_page.widgets.devices_chart', true);
    config()->set('filament-page-visits.metrics_page.widgets.top_referrers', true);
    config()->set('filament-page-visits.metrics_page.widgets.top_asn', true);
});

it('defaults the navigation group to the translated label and allows overriding it', function () {
    $plugin = FilamentPageVisitsPlugin::get();

    expect(PageVisitResource::getNavigationGroup())->toBe(__('filament-page-visits::resources/page-visit.navigation.group'))
        ->and(MetricsPage::getNavigationGroup())->toBe(__('filament-page-visits::resources/page-visit.navigation.group'));

    $plugin->navigationGroup('Custom Group');

    expect(PageVisitResource::getNavigationGroup())->toBe('Custom Group')
        ->and(MetricsPage::getNavigationGroup())->toBe('Custom Group');

    $plugin->navigationGroup(null);
});

it('is a read-only resource', function () {
    $visit = PageVisitFactory::new()->create();

    expect(PageVisitResource::canCreate())->toBeFalse()
        ->and(PageVisitResource::canEdit($visit))->toBeFalse()
        ->and(PageVisitResource::canDelete($visit))->toBeFalse()
        ->and(PageVisitResource::canDeleteAny())->toBeFalse();
});
