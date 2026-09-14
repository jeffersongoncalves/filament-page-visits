<?php

use Illuminate\Support\Facades\Schema;
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

it('renders every metrics widget by default', function () {
    $page = new MetricsPage;

    expect([...$page->getVisibleHeaderWidgets(), ...$page->getVisibleFooterWidgets()])->toEqualCanonicalizing([
        StatsOverview::class,
        SecurityOverview::class,
        HourlyChart::class,
        TrafficTrendChart::class,
        LongTermTrendChart::class,
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
    Schema::drop(config('page-visits.daily_stats_table', 'page_visit_daily_stats'));

    $page = new MetricsPage;

    expect($page->getVisibleHeaderWidgets())->not->toContain(LongTermTrendChart::class);
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
